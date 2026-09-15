<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperMillEdit
 */
class MillEdit extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\MillEditsFactory> */
    use HasFactory;

    /**
     * Should this go on Mill instead?
     * @var array
     */
    public const array OMIT_FROM_DIFF_DISPLAY = [
        'millTypes',
        'woodSpecies',
        'mailing_state_id',
        'state_id',
    ];

    protected $fillable = [
        'mill_id',
        // there's an argument for adding a state_id column to these, even though it can be derived from mill_id
        'submitter_email',
        'submitter_ip',
        'review_hash',
        'url',
        'approve_hash',
        'reject_hash',
        'proposed_changes',
        'sent',
        'sent_to',
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
            // We can't use proposed_changes without converting to string first.
            // We don't have mill_name because we're a MillEdit...
            // So what should we use for potatoes to make hash?
            // We should maybe use something else like Str::ulid() or some such because hashes contain special characters.
            // $potatoes = $me->mill_id.now();
            if (empty($me->approve_hash)) {
                $me->approve_hash = Str::ulid(); // Hash::make("approve:{$potatoes}");
            }

            if (empty($me->reject_hash)) {
                $me->reject_hash = Str::ulid(); // Hash::make("reject:{$potatoes}");
            }

            if (empty($me->review_hash)) {
                $me->review_hash = Str::ulid(); // Hash::make("review:{$potatoes}");
            }
        });

        static::saved(function (MillEdit $me) {
            if (empty($me->url) && $me->status == PublicationStatus::Pending) {
                /**
                 * use the method $request->hasValidSignature() to verify the signature!
                 * We're going to replace this mess with review hash because we can't expire the signed URL.
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

    public function getChanges(): array
    {
        return $this->proposed_changes['changes'] ?? [];
    }

    public function getDiff(): array
    {
        /**
         * This hopefully won't be necessary in the future.
         * I think it was fluke that it was ever needed.
         */
        $diff = $this->proposed_changes['diff'] ?? $this->proposed_changes;
        if (! \is_array($diff)) {
            $diff = ['diff' => $diff];
        }
        return  $diff;
    }

    public function originalMill(string $relationFormat = 'id', ?array $except = []): array
    {
        // Log::debug("\n".self::class."::originalMill():\nBEFORE bookending onlyFormFields():\n");
        $og = $this->mill->onlyFormFields($relationFormat, $except);
        // Log::debug("\n".self::class."::originalMill():\nAFTER bookending onlyFormFields():\n", [
        //     'mill' => $og,
        // ]);
        return $og;
    }

    public function prepareSubmitted(string $relationFormat = 'id', ?array $except = []): array
    {
        /**
         * We need to replace state ids with names!
         * @var Mill
         */
        $submitted = $this->mill->replicate();
        // store changes so we can possibly loop over it later without an existence check
        $changes = $this->getChanges();
        $submitted->fill($changes);

        // Log::debug("\n".self::class."::prepareSubmitted():\nchanges:\n", ['changes' => $changes]);

        // Log::debug("\n\n".self::class."::prepareSubmitted():\nsubmitted before filtering: ", [
        //     'submitted' => $submitted->toArray()
        // ]);

        /**
         * @IMPORTANT
         * if state_id or mailing_state_id are present in $except, we will not have the necessary data to populate
         * state and mailingState because they will be removed before the step where we add them.
         */
        $overlap = array_intersect($except, Mill::STATE_FIELDS);
        if (!empty($overlap)) {
            $except = array_diff($except, Mill::STATE_FIELDS);
        }

        /**
         * We also need to filter form fields.
         * filterFormFields() overwrites the relationship values from changes!
         */
        $submitted = Mill::filterFormFields($submitted, $relationFormat, $except);

        // Log::debug("\n\n".self::class."::prepareSubmitted():\nsubmitted after filtering: ", [
        //     'submitted' => $submitted
        // ]);

        /**
         * Regardless of changes, we need to get the state and mailing state names
         */
        foreach (Mill::STATE_FIELDS as $key) {
            /**
             * We might need isset() instead of empty()
             */
            if (!empty($submitted[$key])) {
                $attr = Str::camel(Str::remove('_id', $key));
                $submitted[$attr] = State::find($submitted[$key])?->name ?? '';
                // Log::debug("\n".self::class."::prepareSubmitted():\n", [
                //     'attr' => $attr,
                //     'key' => $key,
                //     'submitted[attr]' => $submitted[$attr],
                // ]);
            }
        }

        // Log::debug("\n".self::class."::prepareSubmitted():\nsubmitted after state fields: ", [
        //     'submitted' => $submitted
        // ]);

        /**
         * Lastly, double check that relations are added to submitted.
         */
        foreach ($changes as $k => $v) {
            /**
             * I forgot why I added the || !=
             * I remembered why I added the || !=: because of relationships.
             * Submitted might still have the old relationship data.
             * In fact, we should probably check that this still does what we want when the relationships are modified.
             * It does not!
             * And more annoying still, it doesn't pick up the text names for MillTypes or WoodSpecies.
             * Would it perhaps if we moved filterFormFields() below this block?
             */
            if (!isset($submitted[$k]) || $submitted[$k] != $v) {
                // Log::debug(self::class."::prepareSubmitted(): adding missing or differing member '{$k}' to submitted.", [
                //     $k => $v,
                // ]);

                /**
                 * If this is mill_types or wood_species, we need to pull the records and pluck the names...
                 * actually, we might need to rely on relationFormat to determine what form they should take
                 * if $relationFormat is 'id', just assign the values
                 * if 'name', pluck name
                 * if 'id:name', pluck name, key by id
                 */
                // if (\in_array($k, Mill::N_TO_N) && 'id' !== $relationFormat) {
                if (($model = array_search($k, Mill::N_TO_N)) && 'id' !== $relationFormat) {
                    /**
                     * singular() malforms wood_species
                     * instead, append the namespace to the model and let's party
                     */
                    $model = 'App\\Models\\'.$model;
                    if ('name' === $relationFormat) {
                        $v = $model::findMany($v)->pluck('name')->toArray();
                    } else if ('id:name' === $relationFormat) {
                        $v = $model::findMany($v)->pluck('name', 'id')->toArray();
                    }
                }
               
                $submitted[$k] = $v;
            }
        }

        // Log::debug("\n".self::class."::prepareSubmitted():\nsubmitted after all the massaging but before removing overlap:\n", [
        //     'submitted' => $submitted,
        //     'overlap' => $overlap ?? [],
        // ]);


        /**
         * Even more lastly, if there was overlap between except and state fields, we need to remove the overlap.
         */
        $submitted = collect($submitted)->except($overlap)->toArray();

        // Log::debug("\n".self::class."::prepareSubmitted(): submitted after all the massaging and removing overlap:\n", ['submitted' => $submitted]);

        return $submitted;
    }
}
