<?php

namespace App\Http\Controllers;

use App\Mail\MillEditNotification;
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
    public function approve(MillEdit $millEdit)
    {

    }

    public function reject(MillEdit $millEdit)
    {

    }

    /**
     * Perhaps we should pull Mill::create(), store(), edit(), update() into this controller?
     * I say that because those methods actually act on (or soon will) MillEdit models instead of Mills.
     */

    /**
     * show() shows the differences along with approve and reject buttons.
     * @param MillEdit $millEdit
     * @return \Inertia\Response
     */
    public function show(Request $request, MillEdit $millEdit)
    {
        /**
         * signature doesn't work exactly as I'd hoped.
         * It can be checked and verified, but it can't be expired early.
         */
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
         */
        $original = $millEdit->originalMill('name', except: MillEdit::OMIT_FROM_DIFF_DISPLAY);
        $submitted = $millEdit->prepareSubmitted('name', except: MillEdit::OMIT_FROM_DIFF_DISPLAY);
        $changes = $millEdit->getChanges();
        $diff = $millEdit->getDiff();

        // Log::debug(self::class."::show(), changes?", ['changes' => $changes]);

        // Log::debug(self::class."::show(), diff?", ['diff' => $diff]);

        // Log::debug(self::class."::show(): original:", [
        //     'original' => $original,
        // ]);
        // Log::debug(self::class."::show(): submitted: ", [
        //     'submitted' => $submitted,
        // ]);
        // Log::debug(self::class."::show(): millEdit: ", [
        //     'millEdit' => $millEdit->except(['mill']),
        // ]);
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


    /**
     * Preview a MillEdit notification email.
     * Note: this route is only defined when the environment is 'local'.
     *
     * @param MillEdit $millEdit
     * @return MillEditNotification
     */
    public function previewNotification(MillEdit $millEdit)
    {
        return new MillEditNotification($millEdit);
    }

}
