<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $mill_id
 * @property string $submitter_email
 * @property string $submitter_ip
 * @property string $approve_hash
 * @property string $reject_hash
 * @property string $proposed_changes
 * @property string $status
 * @property string|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Mill $mill
 * @method static Builder<static>|MillEdit approved()
 * @method static Builder<static>|MillEdit newModelQuery()
 * @method static Builder<static>|MillEdit newQuery()
 * @method static Builder<static>|MillEdit pending()
 * @method static Builder<static>|MillEdit query()
 * @method static Builder<static>|MillEdit rejected()
 * @method static Builder<static>|MillEdit whereApproveHash($value)
 * @method static Builder<static>|MillEdit whereCreatedAt($value)
 * @method static Builder<static>|MillEdit whereId($value)
 * @method static Builder<static>|MillEdit whereMillId($value)
 * @method static Builder<static>|MillEdit whereProposedChanges($value)
 * @method static Builder<static>|MillEdit whereRejectHash($value)
 * @method static Builder<static>|MillEdit whereReviewedAt($value)
 * @method static Builder<static>|MillEdit whereStatus($value)
 * @method static Builder<static>|MillEdit whereSubmitterEmail($value)
 * @method static Builder<static>|MillEdit whereSubmitterIp($value)
 * @method static Builder<static>|MillEdit whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MillEdit extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\MillEditsFactory> */
    use HasFactory;

    protected $fillable = [
        'mill_id',
        // there's an argument for adding a state_id column to these, even though it can be derived from mill_id
        'submitter_email',
        'submitter_ip',
        'approve_hash',
        'reject_hash',
        'proposed_changes',
        'status',
        'reviewed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The glue.
     */
    public function mill(): BelongsTo
    {
        return $this->belongsTo(Mill::class);
    }

    /**
     * Scopes!
     * as indicated by the #[Scope] attribute/decorator
     */   

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
}
