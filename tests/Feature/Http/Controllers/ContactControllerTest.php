<?php

use App\Jobs\SendContactEmail;
use App\Models\Contact;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * The email rule checks the domain's MX record, so a valid submission needs real DNS. Those tests are grouped
 * 'network'; skip them offline with --exclude-group=network.
 */

beforeEach(function () {
    Bus::fake([SendContactEmail::class]);
});

function contactFormData(array $overrides = []): array
{
    return [
        'name' => 'Pat Forester',
        'email' => 'pat@gmail.com',
        'subject' => 'Missing mill',
        'message' => 'Our mill in Clarke County is missing from the map.',
        ...$overrides,
    ];
}

test('the contact page renders with the honeypot', function () {
    $this->get('/contact')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('contact')->has('honeypot'));
});

test('the old contact URL redirects', function () {
    $this->get('/sec_contact-info')->assertRedirect('/contact')->assertStatus(301);
});

test('a message is stored and emailed', function () {
    $this->post('/contact', contactFormData())
        ->assertRedirect(route('contacts.create'))
        ->assertSessionHasNoErrors();

    $contact = Contact::sole();
    expect($contact)
        ->subject->toBe('Missing mill')
        ->ip_address->toBe('127.0.0.1');
    Bus::assertDispatched(SendContactEmail::class, fn ($job) => $job->contact->is($contact));
})->group('network');

test('the name is optional', function () {
    $this->post('/contact', contactFormData(['name' => null]))->assertSessionHasNoErrors();

    expect(Contact::count())->toBe(1);
})->group('network');

test('invalid messages are sent back with errors', function (array $overrides, string $field) {
    $this->post('/contact', contactFormData($overrides))->assertSessionHasErrors($field);

    expect(Contact::count())->toBe(0);
    Bus::assertNotDispatched(SendContactEmail::class);
})->with([
    'no email' => [['email' => ''], 'email'],
    'malformed email' => [['email' => 'pat-at-gmail'], 'email'],
    'email domain with no mail server' => [['email' => 'pat@nonexistent-domain-zz9.invalid'], 'email'],
    'no subject' => [['subject' => ''], 'subject'],
    'no message' => [['message' => ''], 'message'],
    'message too long' => [['message' => str_repeat('a', 1025)], 'message'],
]);

test('a bot that fills in the honeypot is thanked and ignored', function () {
    $this->post('/contact', contactFormData(['tito_x1' => 'bot']))->assertRedirect();

    expect(Contact::count())->toBe(0);
    Bus::assertNotDispatched(SendContactEmail::class);
});
