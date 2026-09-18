<?php

namespace App\Jobs;

use App\Enums\UserRoles;
use App\Mail\MillEditNotification;
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

class SendMillEditNotification implements ShouldQueue
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
            ->send(new MillEditNotification($this->millEdit));

        if (! $sent) {
            Log::debug("\n".self::class.":\nFailed to send MillEditNotification for MillEdit #{$this->millEdit->id}. Releasing job to attempt resending later.");
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
        /**
         * FFS!
         * Turns out that under the hood, Mail::to() is looking for objects or arrays with props named...
         * ...wait for it...
         * 'email' and 'name'
         */
            ->select(['email', 'name'])
            ->get()
            ->toArray();
        $stateContacts = $this->millEdit->mill->state->stateContacts()
            ->select(['email', 'name'])
            ->get()
            ->toArray();

        return [...$supers, ...$stateContacts];
    }
}
