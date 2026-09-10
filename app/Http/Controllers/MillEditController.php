<?php

namespace App\Http\Controllers;

use App\Models\Mill;
use App\Models\MillEdit;
use Illuminate\Http\Request;
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
     * Perhaps we should pull Mill::create(), store(), edit(), update() into this controller?
     * I say that because those methods actually act on (or soon will) MillEdit models instead of Mills.
     */

    /**
     * Actually, I dont' think we need show().
     * Instead, approve and reject should show the differences and include an "Are you sure?" button...
     * Actually, taking that approach, we don't even need separate hashes for approve and reject.
     * Instead, show() shows the differences along with approve and reject buttons.
     * @param MillEdit $millEdit
     * @return \Inertia\Response
     */
    public function show(Request $request, MillEdit $millEdit)
    {
        /**
         * signature isn't as useful as I'd hoped.
         */
        // $signature = $request->input('signature', null);
        if (! $request->hasValidSignature()) {
            Log::error(self::class."::show(): invalid signature!");
            /**
             * Where to redirect to?
             * 404'd!
             *
             * Or, we could redirect to the Mill that was edited...
             */
            abort(404);
        }
        
        /**
         * We could/should probably extract the mess below into a MillEdit model method.
         * 
         */
        $original = $millEdit->originalMill();
        $submitted = $millEdit->prepareSubmitted();
        $changes = $millEdit->getChanges();
        $diff = $millEdit->getDiff();

        Log::debug(self::class."::show(), changes?", ['changes' => $changes]);

        Log::debug(self::class."::show(), diff?", ['diff' => $diff]);

        Log::debug(self::class."::show(): original:", [
            'original' => $original,
        ]);
        Log::debug(self::class."::show(): submitted: ", [
            'submitted' => $submitted,
        ]);
        Log::debug(self::class."::show(): millEdit: ", [
            'millEdit' => $millEdit->except(['mill']),
        ]);
        // Log::debug(self::class."::show(): mill: ", [
        //     'mill' => $millEdit->mill->toArray(),
        // ]);

        return Inertia::render('mill-edit-show', [
            'original' => $original,
            'submitted' => $submitted,
            'millEdit' => $millEdit,
            'changes' => $changes,
            'diff' => $diff,
        ]);
    }
}
