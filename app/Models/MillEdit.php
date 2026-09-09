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
use Illuminate\Support\Facades\URL;

/**
 * @mixin IdeHelperMillEdit
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
    ];

    protected static function booted(): void
    {
        static::creating(function (MillEdit $me) {
            if (empty($me->approve_hash)) {
                // $me->approve_hash = Hash::make("approve:{$me->proposed_changes}");
                // URL::temporarySignedRoute(
                //     'mill-edits.show',
                //     now()->addDays(30),
                //     ['mill_edit' => $me->id],
                // );
            }

            if (empty($me->reject_hash)) {
                // $me->reject_hash = Hash::make("reject:{$me->proposed_changes}");
                // URL::temporarySignedRoute(
                //     'mill-edits.show',
                //     now()->addDays(30),
                //     ['mill_edit' => $me->id],
                // );
            }

            if (empty($me->url)) {
                $me->url = URL::temporarySignedRoute(
                    'mill-edits.show',
                    now()->addDays(90),
                    ['mill_edit' => $me->id]
                );
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

    protected function pyttIPanna(): string
    {
        return URL::temporarySignedRoute(
            'mill-edits.show',
            now()->addDays(30),
            ['mill_edit' => $this->id],
        );
    }
}
