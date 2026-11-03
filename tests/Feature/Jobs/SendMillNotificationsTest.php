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
 * Who hears about public submissions (ResolvesReviewRecipients): every Superadmin and Administrator, plus the
 * State Agents for the mill's state, or that state's contacts when it has no State Agents yet.
 */

beforeEach(function () {
    $this->state = State::factory()->create();
    $this->otherState = State::factory()->create();

    userWithRole('super@example.com', UserRoles::SUPER);
    User::factory()->create(['email' => 'no-role@example.com']);

    StateContact::factory()->for($this->state)->create(['email' => 'forester@example.com']);
    StateContact::factory()->for($this->otherState)->create(['email' => 'elsewhere@example.com']);
});

function userWithRole(string $email, UserRoles $role, ?State $state = null): User
{
    $user = User::factory()->create(['email' => $email, 'state_id' => $state?->id]);
    $user->assignRole(Role::findOrCreate($role->value, 'web'));

    return $user;
}

function recipientsOf(string $mailable): array
{
    $recipients = [];
    Mail::assertSent($mailable, function ($mail) use (&$recipients) {
        $recipients = collect($mail->to)->pluck('address')->sort()->values()->all();

        return true;
    });

    return $recipients;
}

/**
 * Runs the job for a submission in $state and returns who the email went to.
 */
function notifiedFor(string $job, State $state): array
{
    Mail::fake();

    if ($job === SendMillAddNotification::class) {
        (new SendMillAddNotification(MillEdit::factory()->newBusiness(['state_id' => $state->id])->create()))->handle();

        return recipientsOf(MillAddNotification::class);
    }

    (new SendMillEditNotification(MillEdit::factory()->for(Mill::factory()->for($state))->create()))->handle();

    return recipientsOf(MillEditNotification::class);
}

dataset('notification jobs', [
    'new business' => SendMillAddNotification::class,
    'mill edit' => SendMillEditNotification::class,
]);

test('with no State Agents for the state, it goes to Superadmins and the state\'s contacts', function (string $job) {
    expect(notifiedFor($job, $this->state))->toBe(['forester@example.com', 'super@example.com']);
})->with('notification jobs');

test('Administrators get it too, but other roles don\'t', function (string $job) {
    userWithRole('admin@example.com', UserRoles::ADMIN);
    userWithRole('editor@example.com', UserRoles::EDITOR);

    expect(notifiedFor($job, $this->state))->toBe(['admin@example.com', 'forester@example.com', 'super@example.com']);
})->with('notification jobs');

test('the state\'s State Agents get it instead of its contacts', function (string $job) {
    userWithRole('agent@example.com', UserRoles::AGENT, $this->state);
    userWithRole('agent-two@example.com', UserRoles::AGENT, $this->state);
    userWithRole('other-agent@example.com', UserRoles::AGENT, $this->otherState);

    expect(notifiedFor($job, $this->state))->toBe(['agent-two@example.com', 'agent@example.com', 'super@example.com']);
})->with('notification jobs');

test('a state with neither State Agents nor contacts still notifies Superadmins and Administrators', function (string $job) {
    userWithRole('admin@example.com', UserRoles::ADMIN);
    $bareState = State::factory()->create();

    expect(notifiedFor($job, $bareState))->toBe(['admin@example.com', 'super@example.com']);
})->with('notification jobs');

test('someone with two reasons to get it gets one copy', function (string $job) {
    StateContact::factory()->for($this->state)->create(['email' => 'SUPER@example.com']);

    expect(notifiedFor($job, $this->state))->toBe(['forester@example.com', 'super@example.com']);
})->with('notification jobs');

test('the submission is marked sent, with who it went to', function (string $job) {
    // the real 'array' mailer (phpunit.xml), not Mail::fake(): the job checks send()'s return value, and the fake returns nothing
    userWithRole('agent@example.com', UserRoles::AGENT, $this->state);
    $edit = $job === SendMillAddNotification::class
        ? MillEdit::factory()->newBusiness(['state_id' => $this->state->id])->create()
        : MillEdit::factory()->for(Mill::factory()->for($this->state))->create();

    (new $job($edit))->handle();

    expect($edit->fresh())
        ->sent->toBeTruthy()
        ->sent_to->toContain('agent@example.com', 'super@example.com')
        ->sent_to->not->toContain('forester@example.com');
})->with('notification jobs');
