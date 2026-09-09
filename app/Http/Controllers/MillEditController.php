<?php

namespace App\Http\Controllers;

// use App\Http\Requests\StoreMillEditRequest;
// use App\Http\Requests\UpdateMillEditRequest;
use App\Models\MillEdit;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;


class MillEditController extends Controller
{
    /**
     * How do we check and/or inject the hash?
     * @return void
     */
    public function approve()
    {}

    public function reject()
    {

    }

    /**
     * Actually, I dont' think we need show().
     * Instead, approve and reject should show the differences and include an "Are you sure?" button...
     * Actually, taking that approach, we don't even need separate hashes for approve and reject.
     * Instead, show() shows the differences along with approve and reject buttons.
     * @param MillEdit $millEdit
     * @return \Inertia\Response
     */
    public function show(MillEdit $millEdit)
    {
        Log::debug("MillEditC::show(): WTF? ", [
            'millEdit' => $millEdit->toArray(),
            'mill' => $millEdit->mill->toArray(),
        ]);
        
        $mill = $millEdit->mill;

        $submission = $mill->replicate();

        $submission->fill(json_decode($millEdit->proposed_changes, true));

        return Inertia::render('mill-edit-show', [
            'original' => $mill->original,
            'submission' => $submission,
            'millEdit' => $millEdit,
            'mill' => $mill,
        ]);
    }
}
