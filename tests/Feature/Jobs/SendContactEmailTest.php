<?php

use App\Jobs\SendContactEmail;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;

/**
 * Uses the real 'array' mailer (phpunit.xml) rather than Mail::fake(): the job checks send()'s return value to decide
 * whether it was sent, and the fake returns nothing.
 */
test('a contact message is emailed to the site address and marked sent', function () {
    config(['mail.from.address' => 'team@example.com']);
    $contact = Contact::factory()->create(['sent' => false]);

    (new SendContactEmail($contact))->handle();

    $sent = Mail::mailer()->getSymfonyTransport()->messages();
    expect($sent)->toHaveCount(1)
        ->and($sent->first()->getEnvelope()->getRecipients()[0]->getAddress())->toBe('team@example.com')
        ->and($contact->fresh()->sent)->toBeTruthy();
});
