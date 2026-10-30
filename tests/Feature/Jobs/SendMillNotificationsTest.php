<?php

use App\Enums\UserRoles;
use App\Jobs\SendMillAddNotification;
use App\Jobs\SendMillEditNotification;
use App\Mail\MillAddNotification;
use App\Mail\MillEditNotification;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\State;
use App\Models\StateContact;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

/**
 * Who hears about public submissions: every Superadmin, plus the contacts listed for the mill's state.
 */

beforeEach(function () {
    $this->state = State::factory()->create();
    $this->otherState = State::factory()->create();

    $this->super = User::factory()->create(['email' => 'super@example.com']);
    $this->super->assignRole(Role::findOrCreate(UserRoles::SUPER->value, 'web'));
    User::factory()->create(['email' => 'not-super@example.com']);

    StateContact::factory()->for($this->state)->create(['email' => 'forester@example.com']);
    StateContact::factory()->for($this->otherState)->create(['email' => 'elsewhere@example.com']);
});

function recipientsOf(string $mailable): array
{
    $recipients = [];
    Mail::assertSent($mailable, function ($mail) use (&$recipients) {
        $recipients = collect($mail->to)->pluck('address')->sort()->values()->all();

        return true;
    });

    return $recipients;
}

test('a new business submission goes to Superadmins and the state\'s contacts', function () {
    Mail::fake();
    $edit = MillEdit::factory()->newBusiness(['state_id' => $this->state->id])->create();

    (new SendMillAddNotification($edit))->handle();

    expect(recipientsOf(MillAddNotification::class))->toBe(['forester@example.com', 'super@example.com']);
});

test('a mill edit goes to Superadmins and the mill\'s state\'s contacts', function () {
    Mail::fake();
    $edit = MillEdit::factory()->for(Mill::factory()->for($this->state))->create();

    (new SendMillEditNotification($edit))->handle();

    expect(recipientsOf(MillEditNotification::class))->toBe(['forester@example.com', 'super@example.com']);
});

test('the submission is marked sent, with who it went to', function (string $job, Closure $makeEdit) {
    // the real 'array' mailer (phpunit.xml), not Mail::fake(): the job checks send()'s return value, and the fake returns nothing
    $edit = $makeEdit($this->state);

    (new $job($edit))->handle();

    expect($edit->fresh())
        ->sent->toBeTruthy()
        ->sent_to->toContain('forester@example.com', 'super@example.com');
})->with([
    'new business' => [SendMillAddNotification::class, fn (State $state) => MillEdit::factory()->newBusiness(['state_id' => $state->id])->create()],
    'edit' => [SendMillEditNotification::class, fn (State $state) => MillEdit::factory()->for(Mill::factory()->for($state))->create()],
]);
