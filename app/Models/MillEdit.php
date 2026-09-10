<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * @mixin IdeHelperMillEdit
 */
class MillEdit extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\MillEditsFactory> */
    use HasFactory;

    public const string DEFAULT_URL = '#';

    protected $fillable = [
        'mill_id',
        // there's an argument for adding a state_id column to these, even though it can be derived from mill_id
        'submitter_email',
        'submitter_ip',
        'url',
        'approve_hash',
        'reject_hash',
        'proposed_changes',
        'status',
        'reviewed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'status' => PublicationStatus::class,
        'proposed_changes' => 'array',
    ];

    protected static function booted(): void
    {
            
        static::creating(function (MillEdit $me) {
            // if (empty($me->approve_hash)) {
                // $me->approve_hash = Hash::make("approve:{$me->proposed_changes}");
                // URL::temporarySignedRoute(
                //     'mill-edits.show',
                //     now()->addDays(30),
                //     ['mill_edit' => $me->id],
                // );
            // }

            // if (empty($me->reject_hash)) {
                // $me->reject_hash = Hash::make("reject:{$me->proposed_changes}");
                // URL::temporarySignedRoute(
                //     'mill-edits.show',
                //     now()->addDays(30),
                //     ['mill_edit' => $me->id],
                // );
            // }

            if (empty($me->url)) {
                /**
                 * need to add something because null isn't allowed.
                 * should probably update to allow null?
                 * :shrugs:
                 */
                $me->url = self::DEFAULT_URL;
            }
        });

        static::saved(function (MillEdit $me) {
            if ($me->url === self::DEFAULT_URL) {
                /**
                 * use the method $request->hasValidSignature() to verify the signature!
                 */
                $me->url = URL::temporarySignedRoute(
                    'mill-edits.show',
                    now()->addDays(90),
                    ['mill_edit' => $me]
                );
                $me->save();
            }
        });
    }


    /**
     * The glue.
     */
    public function mill(): BelongsTo
    {
        return $this->belongsTo(Mill::class);
    }

    /*******************************************************************************
     * Scopes!
     * as indicated by the #[Scope] attribute/decorator
     *******************************************************************************/

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', PublicationStatus::Approved);
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', PublicationStatus::Pending);
    }

    #[Scope]
    protected function rejected(Builder $query): void
    {
        $query->where('status', PublicationStatus::Rejected);
    }

    /**
     * This method is only needed because the methods it wraps are protected.
     * We could just make those methods public and ditch this one.
     * @return array{original: array, submitted: array}
     */
    public function forReview(): array
    {
        /**
         * Use the same nomenclature as the view
         * submitted instead of submission
         * only need to store things if we want to log.
         */
        return [
            'original' => $this->originalMill(),
            'submitted' => $this->prepareSubmitted(),
        ];
    }

    public function getChanges(): array
    {
        return $this->proposed_changes['changes'] ?? [];
    }

    public function getDiff(): array
    {
        return $this->proposed_changes['diff'] ?? $this->proposed_changes ?? [];
    }

    public function originalMill(): array
    {
        return $this->mill->onlyFormFields(withRelations: true);
    }

    public function prepareSubmitted(): array
    {
        $submitted = $this->mill->replicate();
        // store changes so we can possibly loop over it later without an existence check
        $changes = $this->getChanges();
        $submitted->fill($changes);
        /**
         * We also need to filter form fields.
         */
        $submitted = Mill::filterFormFields($submitted);

        /**
         * Lastly, double check that relations are added to submitted.
         */
        foreach ($changes as $k => $v) {
            if (!isset($submitted[$k]) || $submitted[$k] != $v) {
                Log::debug(self::class."::prepareSubmitted(): adding missing member '{$k}' to submitted.", [
                    $k => $v,
                ]);
                $submitted[$k] = $v;
            }
        }
        
        return $submitted;
    }
}
