<?php

use App\Enums\PublicationStatus;
use App\Enums\UserRoles;
use App\Jobs\FinalizeMillImport;
use App\Models\Faq;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\MillType;
use App\Models\State;
use App\Models\StatePage;
use App\Models\User;
use App\Models\WoodSpecies;
use App\Support\ActivitySource;
use App\Support\RelationChanges;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

/**
 * The activity log: who changed what, from what, to what, and where the change came from (ActivitySource, the
 * entry's log_name). See AuditsChanges for what's logged, and RelationChanges for many-to-many changes.
 */

function lastActivity(): ?Activity
{
    return Activity::query()->latest('id')->first();
}

describe('what gets logged', function () {
    test('an admin\'s edit records only the changed fields, before and after, and who made it', function () {
        // fresh(): as an admin edit would, start from the stored row (with its column defaults)
        $mill = Mill::factory()->create(['mill_name' => 'Old Name'])->fresh();
        $admin = loginAdminUser(['mills.edit']);

        $mill->update(['mill_name' => 'New Name']);

        $activity = lastActivity();
        expect($activity)
            ->log_name->toBe(ActivitySource::ADMIN)
            ->event->toBe('updated')
            ->subject->is($mill)->toBeTrue()
            ->causer->is($admin)->toBeTrue()
            ->and($activity->properties['old'])->toBe(['mill_name' => 'Old Name'])
            ->and($activity->properties['attributes'])->toBe(['mill_name' => 'New Name']);
    });

    test('a save that changes nothing logs nothing', function () {
        $mill = Mill::factory()->create();
        $before = Activity::count();

        $mill->touch();

        expect(Activity::count())->toBe($before);
    });

    test('creating and deleting are logged too', function () {
        $faq = Faq::factory()->create();
        expect(lastActivity()->event)->toBe('created');

        $faq->delete();
        expect(lastActivity())->event->toBe('deleted')->subject_id->toBe($faq->id);
    });

    test('state pages, mill types, wood species, states, state contacts and page SEO are logged', function (string $model) {
        // StatePage's and StateContact's factories pick an existing state
        $needsState = in_array($model, [StatePage::class, \App\Models\StateContact::class], true);
        $created = $model::factory()->create($needsState ? ['state_id' => State::factory()->create()->id] : []);

        expect(lastActivity()->subject->is($created))->toBeTrue();
    })->with([
        'state page' => StatePage::class,
        'mill type' => MillType::class,
        'wood species' => WoodSpecies::class,
        'state' => State::class,
        'state contact' => \App\Models\StateContact::class,
        'page SEO' => \App\Models\PageSeo::class,
    ]);

    test('roles and permissions are logged', function () {
        $role = \App\Models\Role::create(['name' => 'Reviewer', 'guard_name' => 'web']);

        expect(lastActivity())->event->toBe('created')->subject->is($role)->toBeTrue();
    });

    test('a user\'s password and two-factor secrets are never logged', function () {
        $user = User::factory()->create();

        $user->update(['password' => 'a-brand-new-password', 'name' => 'Renamed']);

        $logged = json_encode(lastActivity()->properties);
        expect($logged)->toContain('Renamed')
            ->not->toContain('a-brand-new-password')
            ->not->toContain($user->fresh()->password)
            ->not->toContain('two_factor');
    });

    test('that a password changed is logged, with a marker in place of the password', function () {
        $user = User::factory()->create();
        $before = Activity::count();

        $user->update(['password' => 'a-brand-new-password']);

        expect(Activity::count())->toBe($before + 1)
            ->and(lastActivity()->properties['attributes'])->toBe(['password' => User::PASSWORD_CHANGED])
            ->and(lastActivity()->properties['old'])->toBe(['password' => '']);
    });

    test('saving a user without changing the password doesn\'t mention it', function () {
        $user = User::factory()->create()->fresh();

        $user->update(['name' => 'Renamed']);

        expect(lastActivity()->properties['attributes'])->not->toHaveKey('password');
    });
});

describe('where a change came from', function () {
    test('with nobody logged in outside a web request (a queue job or artisan command) it\'s "system"', function () {
        Mill::factory()->create();

        expect(lastActivity()->log_name)->toBe(ActivitySource::SYSTEM);
    });

    test('approving a public submission is logged as one, with the submission\'s id', function () {
        $mill = Mill::factory()->create(['mill_name' => 'Before']);
        $edit = MillEdit::factory()->for($mill)->withChanges(['mill_name' => 'After'])->create();
        $admin = loginAdminUser(['mills.approve']);

        $edit->approve();

        $activity = Activity::query()->where('subject_type', $mill->getMorphClass())->where('subject_id', $mill->id)
            ->latest('id')->first();
        expect($activity)
            ->log_name->toBe(ActivitySource::PUBLIC_SUBMISSION)
            ->causer->is($admin)->toBeTrue()
            ->and($activity->properties['mill_edit_id'])->toBe($edit->id)
            ->and($activity->properties['attributes']['mill_name'])->toBe('After');
    });

    test('after as() finishes, changes go back to their usual source', function () {
        ActivitySource::as(ActivitySource::IMPORT, fn () => null, ['import_id' => 1]);

        Mill::factory()->create();

        expect(lastActivity())->log_name->toBe(ActivitySource::SYSTEM)
            ->and(lastActivity()->properties->has('import_id'))->toBeFalse();
    });
});

describe('imports', function () {
    beforeEach(function () {
        $this->state = State::factory()->create();
        $this->oldImport = makeImport($this->state->id);
        $this->replaced = makePendingMill([
            'mill_name' => 'Replaced Mill',
            'state_id' => $this->state->id,
            'import_id' => $this->oldImport->id,
            'status' => PublicationStatus::Approved,
        ]);
        $this->import = makeImport($this->state->id, ['user_id' => User::factory()->create()->id]);
    });

    test('mills aren\'t logged one by one while the import works on them', function () {
        $before = Activity::count();

        $mill = makePendingMill(['state_id' => $this->state->id, 'import_id' => $this->import->id]);
        $mill->update(['telephone' => '706-555-0199']);

        expect(Activity::count())->toBe($before);
    });

    test('finishing an import logs one summary entry, with who ran it and which mills it replaced', function () {
        makePendingMill(['state_id' => $this->state->id, 'import_id' => $this->import->id]);
        makePendingMill(['state_id' => $this->state->id, 'import_id' => $this->import->id]);
        $before = Activity::count();

        (new FinalizeMillImport($this->import->id, $this->state->id, deleteFromState: true))->handle();

        expect(Activity::count())->toBe($before + 1);
        $activity = lastActivity();
        expect($activity)
            ->log_name->toBe(ActivitySource::IMPORT)
            ->event->toBe('finalized')
            ->causer_id->toBe($this->import->user_id)
            ->and($activity->properties['published'])->toBe(2)
            ->and($activity->properties['removed_mills'])->toBe([$this->replaced->id => 'Replaced Mill']);
    });

    test('a mill the import has published is logged as usual from then on', function () {
        $mill = makePendingMill(['state_id' => $this->state->id, 'import_id' => $this->import->id]);
        (new FinalizeMillImport($this->import->id, $this->state->id))->handle();

        freshMill($mill)->update(['telephone' => '706-555-0123']);

        expect(lastActivity())->event->toBe('updated')->subject_id->toBe($mill->id);
    });
});

describe('many-to-many changes', function () {
    test('a mill\'s types and species changes are logged as names, before and after', function () {
        $mill = Mill::factory()->create();
        [$sawmill, $pallet] = [MillType::factory()->create(['name' => 'Sawmill']), MillType::factory()->create(['name' => 'Pallet'])];
        $mill->millTypes()->sync([$sawmill->id]);

        $before = RelationChanges::snapshot($mill, ['millTypes', 'woodSpecies']);
        $mill->millTypes()->sync([$sawmill->id, $pallet->id]);
        RelationChanges::log($mill, $before);

        expect(lastActivity())
            ->description->toBe('updated mill types')
            ->and(lastActivity()->properties['old'])->toBe(['millTypes' => 'Sawmill'])
            ->and(lastActivity()->properties['attributes'])->toBe(['millTypes' => 'Pallet, Sawmill']);
    });

    test('an unchanged relation logs nothing', function () {
        $mill = Mill::factory()->create();
        $before = Activity::count();

        RelationChanges::log($mill, RelationChanges::snapshot($mill, ['millTypes']));

        expect(Activity::count())->toBe($before);
    });

    test('changing a user\'s role in the admin panel is logged', function () {
        // a Superadmin (anyone else can only edit their own account), who can edit roles and permissions (or the form
        // leaves the roles field out)
        loginAdminUser(['users.edit', 'roles.edit', 'permissions.edit'], UserRoles::SUPER);
        $person = User::factory()->create();
        $person->assignRole(Role::findOrCreate(UserRoles::EDITOR->value, 'web'));
        $admin = Role::findOrCreate(UserRoles::ADMIN->value, 'web');

        $this->put("/admin/user/{$person->id}", [
            'id' => $person->id,
            'name' => $person->name,
            'email' => $person->email,
            'password' => '',
            'password_confirmation' => '',
            'roles' => [$admin->id],
            'permissions' => [],
            'agent_role_name' => UserRoles::AGENT->value,
            'is_agent' => 'false',
        ])->assertSessionHasNoErrors();

        $activity = Activity::query()->where('description', 'updated roles')->latest('id')->first();
        expect($activity)->not->toBeNull()
            ->subject_id->toBe($person->id)
            ->log_name->toBe(ActivitySource::ADMIN)
            ->and($activity->properties['old'])->toBe(['roles' => UserRoles::EDITOR->value])
            ->and($activity->properties['attributes'])->toBe(['roles' => UserRoles::ADMIN->value]);
    });
});

describe('the activity log screen', function () {
    test('needs activity_log.see', function () {
        loginAdminUser(['mills.edit'], UserRoles::ADMIN);

        $this->get('/admin/activity-log')->assertForbidden();
    });

    test('so do its filter lookups', function () {
        loginAdminUser(['mills.edit'], UserRoles::ADMIN);

        $this->getJson('/admin/activity-log/causer')->assertForbidden();
    });

    test('opens for someone with it', function () {
        loginAdminUser(['activity_log.see'], UserRoles::SUPER);

        $this->get('/admin/activity-log')->assertOk();
    });

    test('lists entries', function () {
        Mill::factory()->create(['mill_name' => 'Listed Mill']);
        loginAdminUser(['activity_log.see'], UserRoles::SUPER);

        expect(listRows($this, 'activity-log')['recordsTotal'])->toBeGreaterThan(0);
    });
});
