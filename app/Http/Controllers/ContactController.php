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
        $all = $request->all();
        $data = $request->validated();
        Log::debug("\n".self::class."::store():\nContact form submission: \n", [
            "\nall\n" => $all,
            "\nvalidated\n" => $data
        ]);
        /**
         * As suspected, the Honeypot fields get added to the form.
         * However, it doesn't seem to block anything.
         * At this point, we could block it ourselves by checking the time and making sure the dummy field is empty
         */


        /**
         * Should we do an empty check?
         */
        $contact = Contact::create($data);

        $msg = \sprintf('Contact form submission %d stored!', $contact->id);
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
