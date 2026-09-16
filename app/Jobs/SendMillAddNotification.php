<?php

namespace App\Jobs;

use App\Enums\UserRoles;
use App\Mail\MillAddNotification;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\State;
use App\Models\StateContact;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMillAddNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MillEdit $millEdit
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // figure out who all to send the notification to
        /**
         * 1. site admins: me & Daniel + possible a more generic sref.info account
         *      pull site admin users?
         * 2. state contacts
         *      pull $millEdit->mill->state->stateContacts?
         * 
         */
        // store toWhom so we can update the record accordingly
        $toWhom = $this->resolveToWhom();
        $sent = Mail::to($toWhom)
            ->send(new MillAddNotification($this->millEdit));

        if (! $sent) {
            Log::debug("\n".self::class.":\nFailed to send MillAddNotification for MillEdit #{$this->millEdit->id}. Releasing job to attempt resending later.");
            $this->release(10);
            return;
        }

        /**
         * If sent, update the record
         */
        $this->millEdit->update([
            'sent' => true,
            'sent_to' => Arr::flatten($toWhom), // probably need to cast this to a string
        ]);
    }

    protected function resolveToWhom(): array
    {
        $supers = User::role(UserRoles::SUPER)
            ->select(['email', 'name'])
            ->get()
            ->toArray();

        /**
         * We have to fetch the state first because we don't have a Mill yet.
         */
        $changes = $this->millEdit->getChanges();
        $state = State::find($changes['state_id']);
        $stateContacts = $state->stateContacts()
            ->select(['email', 'name'])
            ->get()
            ->toArray();

        return [...$supers, ...$stateContacts];
    }
}
