<?php

use App\Enums\UserRoles;
use App\Jobs\ProcessMill;
use App\Jobs\SendMillEditApprovalNotice;
use App\Jobs\SendMillEditRejectionNotice;
use App\Mail\MillEditApprovalNotice;
use App\Mail\MillEditRejectionNotice;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\State;
use App\Models\StateContact;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

/**
 * The approve/reject notices sent to whoever submitted a new business or an edit. They go to the submitter, with
 * Superadmins and Administrators BCC'd. State Agents and state contacts aren't copied.
 */

beforeEach(function () {
    // approve() for a new business dispatches nothing itself, but keep geocoding etc. out of the way regardless
    Bus::fake([ProcessMill::class]);

    $this->state = State::factory()->create();
    $this->mill = Mill::factory()->for($this->state)->create(['mill_name' => 'Old Name Lumber']);

    noticeUserWithRole('super@example.com', UserRoles::SUPER);
    noticeUserWithRole('admin@example.com', UserRoles::ADMIN);
    noticeUserWithRole('editor@example.com', UserRoles::EDITOR);
    noticeUserWithRole('agent@example.com', UserRoles::AGENT, $this->state);
    StateContact::factory()->for($this->state)->create(['email' => 'forester@example.com']);
});

function noticeUserWithRole(string $email, UserRoles $role, ?State $state = null): User
{
    $user = User::factory()->create(['email' => $email, 'state_id' => $state?->id]);
    $user->assignRole(Role::findOrCreate($role->value, 'web'));

    return $user;
}

/** A pending edit to $this->mill, by submitter@example.com. */
function pendingEdit(Mill $mill, array $changes = ['mill_name' => 'New Name Lumber']): MillEdit
{
    return MillEdit::factory()->for($mill)->withChanges($changes)->create(['submitter_email' => 'submitter@example.com']);
}

/** A pending new business submission, by submitter@example.com. */
function pendingNewBusiness(State $state, string $name = 'Brand New Mill'): MillEdit
{
    return MillEdit::factory()->newBusiness(['mill_name' => $name, 'state_id' => $state->id])
        ->create(['submitter_email' => 'submitter@example.com']);
}

dataset('notices', [
    'approval' => [SendMillEditApprovalNotice::class, MillEditApprovalNotice::class, 'approve'],
    'rejection' => [SendMillEditRejectionNotice::class, MillEditRejectionNotice::class, 'reject'],
]);

describe('who gets it', function () {
    test('it goes to the submitter, with Superadmins and Administrators BCC\'d', function (string $job, string $mailable, string $review) {
        Mail::fake();
        $edit = pendingEdit($this->mill);
        $edit->{$review}();

        (new $job($edit->fresh()))->handle();

        Mail::assertSent($mailable, function ($mail) {
            expect(collect($mail->to)->pluck('address')->all())->toBe(['submitter@example.com'])
                ->and(collect($mail->bcc)->pluck('address')->sort()->values()->all())->toBe(['admin@example.com', 'super@example.com']);

            return true;
        });
    })->with('notices');

    test('State Agents, state contacts and other roles aren\'t copied', function (string $job, string $mailable, string $review) {
        Mail::fake();
        $edit = pendingEdit($this->mill);
        $edit->{$review}();

        (new $job($edit->fresh()))->handle();

        Mail::assertSent($mailable, function ($mail) {
            $everyone = collect([...$mail->to, ...$mail->cc, ...$mail->bcc])->pluck('address');

            expect($everyone)->not->toContain('agent@example.com', 'forester@example.com', 'editor@example.com');

            return true;
        });
    })->with('notices');

    test('the submission is marked as notified once sent', function (string $job, string $mailable, string $review) {
        // the real 'array' mailer (phpunit.xml), not Mail::fake(): the job checks send()'s return value, and the fake returns nothing
        $edit = pendingEdit($this->mill);
        $edit->{$review}();

        expect($edit->fresh()->submitter_notified_at)->toBeNull();

        (new $job($edit->fresh()))->handle();

        expect($edit->fresh()->submitter_notified_at)->not->toBeNull();
    })->with('notices');
});

describe('approval email', function () {
    test('for an edit, it names the mill and links to its listing', function () {
        $edit = pendingEdit($this->mill);
        $edit->approve();

        (new MillEditApprovalNotice($edit->fresh()))
            ->assertHasSubject(config('mail.test.subject.prefix') . 'Your edits for "New Name Lumber" have been approved.')
            ->assertSeeInHtml(route('mills.show', $this->mill->fresh()), false)
            ->assertSeeInHtml('View the listing for New Name Lumber', false);
    });

    test('for a new business, it says the submission was approved and links to the new listing', function () {
        $edit = pendingNewBusiness($this->state);
        $edit->approve();
        $mill = Mill::where('mill_name', 'Brand New Mill')->firstOrFail();

        (new MillEditApprovalNotice($edit->fresh()))
            ->assertHasSubject(config('mail.test.subject.prefix') . 'Your new mill submission for "Brand New Mill" has been approved.')
            ->assertSeeInHtml(route('mills.show', $mill), false);
    });
});

describe('rejection email', function () {
    test('for an edit, it names the mill', function () {
        $edit = pendingEdit($this->mill);
        $edit->reject();

        (new MillEditRejectionNotice($edit->fresh()))
            ->assertHasSubject(config('mail.test.subject.prefix') . 'Your edits for "Old Name Lumber" have been rejected.');
    });

    test('for a new business, it names the submitted mill', function () {
        $edit = pendingNewBusiness($this->state, 'Spam Mill');
        $edit->reject();

        (new MillEditRejectionNotice($edit->fresh()))
            ->assertHasSubject(config('mail.test.subject.prefix') . 'Your new mill submission for "Spam Mill" has been rejected.');
    });

    test('the reviewer\'s reason is included', function () {
        $edit = pendingEdit($this->mill);
        $edit->reject('This mill closed in 2019.');

        (new MillEditRejectionNotice($edit->fresh()))
            ->assertSeeInHtml('rejected for the reason(s) below', false)
            ->assertSeeInHtml('This mill closed in 2019.', false);
    });

    test('with no reason, the reason section is left out', function () {
        $edit = pendingEdit($this->mill);
        $edit->reject();

        (new MillEditRejectionNotice($edit->fresh()))
            ->assertDontSeeInHtml('reason(s) below', false)
            ->assertSeeInHtml('rejected in error', false);
    });

    test('the reason is escaped', function () {
        $edit = pendingEdit($this->mill);
        $edit->reject('<script>alert(1)</script>');

        (new MillEditRejectionNotice($edit->fresh()))
            ->assertDontSeeInHtml('<script>alert(1)</script>', false);
    });
});

describe('both emails', function () {
    test('replies go to the site\'s from address', function (string $job, string $mailable, string $review) {
        $edit = pendingEdit($this->mill);
        $edit->{$review}();

        (new $mailable($edit->fresh()))
            ->assertFrom(config('mail.from.address'))
            ->assertHasReplyTo(config('mail.from.address'));
    })->with('notices');

    test('in production the subject has no testing prefix', function (string $job, string $mailable, string $review) {
        config(['app.env' => 'production']);
        $edit = pendingEdit($this->mill);
        $edit->{$review}();

        $subject = (new $mailable($edit->fresh()))->envelope()->subject;

        expect($subject)->not->toStartWith(config('mail.test.subject.prefix'))
            ->and($subject)->toStartWith('Your edits for');
    })->with('notices');
});
