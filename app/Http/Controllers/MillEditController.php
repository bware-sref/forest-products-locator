<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Mail\MillEditNotification;
use App\Models\Mill;
use App\Models\MillEdit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class MillEditController extends Controller
{
    /**
     * How do we check and/or inject the hash?
     */
    public function approve(MillEdit $millEdit)
    {
        self::pendingOrDie($millEdit);

        /**
         * I think it might be as simple as using fill(), then looping over the N_to_N relations.
         * In any case, that stuff should go in a model method.
         * I suppose we should wrap this in try{}
         */

        try {
            $millEdit->approve();
        } catch (Throwable $e) {
            $msg = "Failed to process approval of MillEdit #{$millEdit->id}!";
            Log::error("\n".self::class."::approve():\n{$msg}", [
                "\nerror\n" => $e->getMessage(),
                "\nmillEdit\n" => $millEdit->toArray(),
            ]);
            Inertia::flash([
                'type' => 'error',
                'message' => $msg,
            ]);
            return to_route('mill-edits.show', $millEdit);
        }

        $msg = "Approved MillEdit #{$millEdit->id} for Mill #{$millEdit->mill->id}.";
        Log::debug("\n".self::class."::approve():\n{$msg}");
        Inertia::flash([
            'type' => 'success',
            'message' => $msg,
        ]);

        return to_route('mills.show', $millEdit->mill);
    }

    public function reject(MillEdit $millEdit)
    {
        self::pendingOrDie($millEdit);

        if (! $millEdit->reject()) {
            /**
             * What to do if it fails?
             */
            $msg = "Failed to reject MillEdit #{$millEdit->id}";
            Log::error("\n".self::class."::rejected():\n{$msg}");
            Inertia::flash([
                'type' => 'error',
                'message' => $msg,
            ]);
            return to_route('mill-edits.show', $millEdit);
        }

        $msg = "Rejected MillEdit #{$millEdit->id}.";
        Log::debug("\n".self::class."::rejected():\n{$msg}\n");
        Inertia::flash([
            'type' => 'success',
            'message' => $msg,
        ]);
        return to_route('mills.show', $millEdit->mill);
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
        self::pendingOrDie($millEdit);
        
        /**
         * We could/should probably extract the mess below into a MillEdit model method.
         */
        $original = $millEdit->originalMill();
        $submitted = $millEdit->prepareSubmitted();
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
            // I don't think we need changes or diff in the view
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

    /**
     * Should we just pass the status?
     * @param MillEdit $millEdit
     * @return void
     */
    protected static function pendingOrDie(MillEdit $millEdit)
    {
        if ($millEdit->status === PublicationStatus::Pending) {
            return;
        }
        Log::error("\n".self::class."::pendingOrDie():\nattempt to approve, reject, or review MillEdits which are no longer pending?!?\n", [
            'millEdit' => $millEdit->toArray(),
        ]);
        /**
         * Where to redirect to?
         * 404'd!
         */
        abort(404);
    }
}
