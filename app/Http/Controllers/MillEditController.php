<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Jobs\ProcessMill;
use App\Mail\MillAddNotification;
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
     * Approve a MillEdit
     */
    public function approve(MillEdit $millEdit)
    {
        self::pendingOrDie($millEdit);

        /**
         * I think it might be as simple as using fill(), then looping over the N_to_N relations.
         * In any case, that stuff should go in a model method.
         * I suppose we should wrap this in try{}
         * 
         * Hmm...we need to send this to ProcessMill if the address changed, or if it's a new Mill.
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
        
        /**
         * Dispatch a ProcessMill job to make sure coordinates, et al, are filled in.
         * @todo test it
         * Yeah, I think we need to refresh the $millEdit before dispatching.
         */
        $millEdit->refresh();
        ProcessMill::dispatch($millEdit->mill);

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

        /**
         * The only thing that changes when we reject a new mill is where we redirect to
         */
        if ($millEdit->isNewMill()) {
            return to_route('home');
        }

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
    public function show(MillEdit $millEdit)
    {
        self::pendingOrDie($millEdit);

        /**
         * we need this for all paths
         */
        $submitted = $millEdit->prepareSubmitted();

        /**
         * If there's no mill, this is a new mill submission
         */
        if ($millEdit->isNewMill()) {
            return Inertia::render('review-new-mill', [
                'submitted' => $submitted,
                'millEdit' => $millEdit,
            ]);
        }

        /**
         * We extracted the mess below into a MillEdit model method.
         */
        $original = $millEdit->originalMill();

        return Inertia::render('mill-edit-show', [
            'original' => $original,
            'submitted' => $submitted,
            'millEdit' => $millEdit,
            // I don't think we need changes or diff in the view
        ]);
    }

    /**
     * Preview a MillEdit notification email.
     * Note: this route is only defined when the environment is 'local'.
     *
     * @param MillEdit $millEdit
     * @return MillAddNotification | MillEditNotification
     */
    public function previewNotification(MillEdit $millEdit)
    {
        if ($millEdit->isNewMill()) {
            return new MillAddNotification($millEdit);
        }
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
