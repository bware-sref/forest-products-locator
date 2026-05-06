<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Jobs\SendContactEmail;
use App\Mail\ContactEmail;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('contact', []);
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
