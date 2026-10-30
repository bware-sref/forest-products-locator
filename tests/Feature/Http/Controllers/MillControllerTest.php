<?php

use App\Enums\PublicationStatus;
use App\Jobs\SendMillAddNotification;
use App\Jobs\SendMillEditNotification;
use App\Models\County;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\MillType;
use App\Models\State;
use App\Models\WoodSpecies;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    Bus::fake([SendMillAddNotification::class, SendMillEditNotification::class]);

    $this->state = State::factory()->create();
});

/**
 * What the Add Your Business / Edit Mill form posts.
 */
function millFormData(State $state, array $overrides = []): array
{
    return [
        'mill_name' => 'Timber Creek Lumber',
        'physical_address' => '123 Timber Rd',
        'physical_city' => 'Athens',
        'state_id' => $state->id,
        'physical_zip' => '30601',
        'mailing_address_same_as_physical' => true,
        'telephone' => '706-555-0100',
        'email' => 'info@timbercreek.example',
        'mill_types' => [],
        'wood_species' => [],
        'submitter_email' => 'owner@timbercreek.example',
        ...$overrides,
    ];
}

/**
 * The form fields of an existing mill, as the edit form would post them back unchanged.
 */
function editFormData(Mill $mill, array $overrides = []): array
{
    return [
        ...$mill->onlyFormFields('id'),
        'submitter_email' => 'owner@timbercreek.example',
        ...$overrides,
    ];
}

describe('browsing', function () {
    test('the list and map only offer states that have mills', function (string $path, string $component) {
        Mill::factory()->for($this->state)->create();
        State::factory()->create(); // no mills

        $this->get($path)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->has('states', 1)
                ->where('states.0.id', $this->state->id)
                ->where('millsApiUrl', route('api.v1.mills.index'))
            );
    })->with([
        'list' => ['/mill-list', 'mill-list-page'],
        'map' => ['/mill-map', 'mill-map-page'],
    ]);

    test('a mill\'s page shows the mill, with its county and state in the description', function () {
        $county = County::forceCreate(['name' => 'Clarke', 'type' => 'county', 'state_id' => $this->state->id]);
        $mill = Mill::factory()->for($this->state)->create(['mill_name' => 'Timber Creek Lumber', 'county_id' => $county->id]);

        $this->get("/mill-list/{$mill->match_id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mill-single')
                ->where('mills.0.id', $mill->id)
                ->where('pageSeo.description', fn (string $description) => str_contains(
                    $description,
                    "Timber Creek Lumber is a forest products company in Clarke, {$this->state->abbreviation}."
                ))
            );
    });

    test('unpublished mills have no page', function (string $state) {
        $mill = Mill::factory()->for($this->state)->{$state}()->create();

        $this->get("/mill-list/{$mill->match_id}")->assertNotFound();
        $this->get("/mills/{$mill->match_id}/edit")->assertNotFound();
    })->with(['pending', 'rejected', 'invalid']);

    test('the export downloads a spreadsheet', function () {
        Excel::fake();
        Mill::factory()->for($this->state)->count(2)->create();

        $this->get('/mills/export')->assertOk();

        Excel::assertDownloaded('mills.xlsx');
    });
});

describe('adding a business', function () {
    test('the form offers states with their counties', function () {
        County::forceCreate(['name' => 'Clarke', 'type' => 'county', 'state_id' => $this->state->id]);

        $this->get('/add-business')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('add-business')
                ->has('honeypot')
                ->where('states', fn ($states) => collect($states)->firstWhere('id', $this->state->id)['counties'][0]['name'] === 'Clarke')
            );
    });

    test('a submission is held for review, not published', function () {
        $sawmill = MillType::factory()->create();
        $pine = WoodSpecies::factory()->create();

        $this->post('/mills', millFormData($this->state, ['mill_types' => [$sawmill->id], 'wood_species' => [$pine->id]]))
            ->assertRedirect(route('mills.create'))
            ->assertSessionHasNoErrors();

        $edit = MillEdit::sole();
        expect($edit)
            ->isNewMill()->toBeTrue()
            ->status->toBe(PublicationStatus::Pending)
            ->submitter_email->toBe('owner@timbercreek.example')
            ->submitter_ip->toBe('127.0.0.1')
            ->and($edit->getChanges())
                ->mill_name->toBe('Timber Creek Lumber')
                ->mill_types->toBe([$sawmill->id])
                ->wood_species->toBe([$pine->id])
            ->and(Mill::withoutGlobalScopes()->count())->toBe(0);
    });

    test('a submission notifies the reviewers', function () {
        $this->post('/mills', millFormData($this->state));

        Bus::assertDispatched(SendMillAddNotification::class, fn ($job) => $job->millEdit->is(MillEdit::sole()));
    });

    test('"same as physical" copies the physical address to the mailing address', function () {
        $this->post('/mills', millFormData($this->state, ['mailing_address' => '', 'mailing_city' => '']));

        expect(MillEdit::sole()->getChanges())
            ->mailing_address->toBe('123 Timber Rd')
            ->mailing_city->toBe('Athens')
            ->mailing_state_id->toBe($this->state->id)
            ->mailing_zip->toBe('30601');
    });

    test('invalid submissions are sent back with errors', function (array $overrides, string $field) {
        // a null override means "leave the field out"
        $data = array_filter(millFormData($this->state, $overrides), fn ($value) => null !== $value);

        $this->post('/mills', $data)
            ->assertSessionHasErrors($field);

        expect(MillEdit::count())->toBe(0);
        Bus::assertNotDispatched(SendMillAddNotification::class);
    })->with([
        'no name' => [['mill_name' => ''], 'mill_name'],
        'no state' => [['state_id' => null], 'state_id'],
        'unknown state' => [['state_id' => 999999], 'state_id'],
        'bad zip' => [['physical_zip' => '3060'], 'physical_zip'],
        'bad phone' => [['telephone' => '555-0100'], 'telephone'],
        'bad website' => [['web_site' => 'not a url'], 'web_site'],
        'future year' => [['year' => (int) date('Y') + 1], 'year'],
        'unknown mill type' => [['mill_types' => [999999]], 'mill_types.0'],
        'no submitter email' => [['submitter_email' => null], 'submitter_email'],
        'empty submitter email' => [['submitter_email' => ''], 'submitter_email'],
        'bad submitter email' => [['submitter_email' => 'nope'], 'submitter_email'],
    ]);

    test('a bot that fills in the honeypot is thanked and ignored', function () {
        $this->post('/mills', millFormData($this->state, ['tito_x1' => 'I am definitely a human']))
            ->assertRedirect();

        expect(MillEdit::count())->toBe(0);
        Bus::assertNotDispatched(SendMillAddNotification::class);
    });
});

describe('editing a mill', function () {
    beforeEach(function () {
        $this->mill = Mill::factory()->for($this->state)->create(['mill_name' => 'Old Name Lumber']);
    });

    test('the form is filled in with the mill', function () {
        $this->get("/mills/{$this->mill->match_id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('add-business')
                ->where('mill.id', $this->mill->id)
                ->where('pageTitle', 'Edit Mill')
            );
    });

    test('changes are held for review, and the mill is left alone', function () {
        $this->post("/mills/{$this->mill->match_id}", editFormData($this->mill, ['mill_name' => 'New Name Lumber']))
            ->assertRedirect(route('mills.show', $this->mill));

        $edit = MillEdit::sole();
        expect($edit)
            ->mill_id->toBe($this->mill->id)
            ->status->toBe(PublicationStatus::Pending)
            ->and($edit->getDiff()['mill_name'])->toEqual(['from' => 'Old Name Lumber', 'to' => 'New Name Lumber'])
            ->and($this->mill->fresh()->mill_name)->toBe('Old Name Lumber');
        Bus::assertDispatched(SendMillEditNotification::class);
    });

    test('changing only the mill types counts as a change', function () {
        $sawmill = MillType::factory()->create();

        $this->post("/mills/{$this->mill->match_id}", editFormData($this->mill, ['mill_types' => [$sawmill->id]]));

        expect(MillEdit::sole()->getChanges()['mill_types'])->toBe([$sawmill->id]);
    });

    test('submitting the form unchanged doesn\'t bother the reviewers', function () {
        // with types and species attached, since those always come back in the form's data
        $this->mill->millTypes()->attach(MillType::factory()->create());
        $this->mill->woodSpecies()->attach(WoodSpecies::factory()->create());

        $this->post("/mills/{$this->mill->match_id}", editFormData($this->mill))
            ->assertRedirect(route('mills.show', $this->mill));

        expect(MillEdit::count())->toBe(0);
        Bus::assertNotDispatched(SendMillEditNotification::class);
    });

    test('an edit without a submitter email is sent back with errors', function () {
        $data = editFormData($this->mill, ['mill_name' => 'New Name Lumber']);
        unset($data['submitter_email']);

        $this->post("/mills/{$this->mill->match_id}", $data)->assertSessionHasErrors('submitter_email');

        expect(MillEdit::count())->toBe(0);
    });

    test('invalid changes are sent back with errors', function () {
        $this->post("/mills/{$this->mill->match_id}", editFormData($this->mill, ['mill_name' => '']))
            ->assertSessionHasErrors('mill_name');

        expect(MillEdit::count())->toBe(0);
    });

    test('a bot that fills in the honeypot is sent back to the mill', function () {
        // the edit form remembers where to send spam
        $this->get("/mills/{$this->mill->match_id}/edit");

        $this->post("/mills/{$this->mill->match_id}", editFormData($this->mill, ['mill_name' => 'Spam', 'tito_x1' => 'bot']))
            ->assertRedirect(route('mills.show', $this->mill));

        expect(MillEdit::count())->toBe(0);
    });
});
