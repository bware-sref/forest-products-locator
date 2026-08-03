<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Jobs\SendContactEmail;
use App\Models\Contact;
use App\Models\PageSeo;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ContactController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('contact', [
            'pageTitle' => 'Contact',
            'pageSeo' => PageSeo::resolve(
                'contact',
                'Contact',
                'Get in touch with the Forest Products Locator team.'
            ),
        ]);
    }

    public function store(StoreContactRequest $request)
    {
        // do stuff
        $data = $request->validated();
        Log::debug('Contact form submission: ', $data);

        $contact = Contact::create($data);

        $msg = sprintf('Contact form submission %d stored!', $contact->id);
        Log::debug($msg);

        // try to send the email here?
        // or put it on the job queue to send later and update the record as sent?
        // try to send it here for now so we don't have to fuck with the job queue
        SendContactEmail::dispatch($contact);

        Inertia::flash([
            'type' => 'success',
            'message' => 'Thank you for submitting a contact request.',
        ]);

        return to_route('contact');
    }
}
