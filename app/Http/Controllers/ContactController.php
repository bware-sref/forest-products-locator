<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Jobs\SendContactEmail;
use App\Mail\ContactEmail;
use App\Models\Contact;
use App\Models\PageSeo;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Honeypot\Honeypot;

class ContactController extends Controller
{
    /**
     * This action should more properly be named "create" rather than "index".
     * @return \Inertia\Response
     */
    public function index(Honeypot $honeypot)
    {
        return Inertia::render('contact', [
            'pageTitle' => 'Contact',
            'pageSeo' => PageSeo::resolve(
                'contact',
                'Contact',
                'Get in touch with the Forest Products Locator team.'
            ),
            'honeypot' => $honeypot,
        ]);
    }

    public function store(StoreContactRequest $request)
    {
        // do stuff
        $data = $request->validated();
        Log::debug("\n".self::class."::store():\nContact form submission: \n", [
            "\nvalidated\n" => $data
        ]);

        /**
         * Should we do an empty check and possibly show an error?
         */
        $contact = Contact::create($data);

        if (empty($contact)) {
            $msg = "An error occurred while sending your message. Please try again later.";
            Log::error("\n".self::class."::store(): {$msg}", [
                "\nvalidated form data:\n" => $data,
            ]);
            Inertia::flash([
                'type' => 'error',
                'message' => $msg,
            ]);
            return to_route('contacts.create');
        }

        $msg = "Contact form submission {$contact->id} stored!";
        Log::debug($msg);

        // try to send the email here?
        // or put it on the job queue to send later and update the record as sent?
        // try to send it here for now so we don't have to fuck with the job queue
        SendContactEmail::dispatch($contact);

        Inertia::flash([
            'type' => 'success',
            'message' => 'Thank you for submitting a contact request.',
        ]);

        return to_route('contacts.create');
    }

    public function preview(Contact $contact)
    {
        return new ContactEmail($contact);
    }
}
