<?php

namespace App\Models;

use App\Enums\MillSubmissionStatus;
use App\Enums\MillSubmissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MillSubmission extends Model
{
    protected $fillable = [
        'mill_id',
        'state_id',
        /**
         * Type can be inferred from mill_id: if null, new; if not null, edit.
         */
        // 'type',
        'payload',
        'submitter_name',
        'submitter_email',
        'submitter_ip',
        'status',
        'reviewed_by',
        'reviewed_at',
        'notes',
    ];

    protected $casts = [
        'payload'     => 'array',
        // 'type'        => MillSubmissionType::class,
        'status'      => MillSubmissionStatus::class,
        'reviewed_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function mill(): BelongsTo
    {
        return $this->belongsTo(Mill::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * The user who reviewed this submission.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', MillSubmissionStatus::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', MillSubmissionStatus::Approved);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', MillSubmissionStatus::Rejected);
    }

    public function scopeForState($query, int $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    public function scopeNewMills($query)
    {
        return $query->where('type', MillSubmissionType::New);
    }

    public function scopeEdits($query)
    {
        return $query->where('type', MillSubmissionType::Edit);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isNew(): bool
    {
        return $this->type === MillSubmissionType::New;
    }

    public function isEdit(): bool
    {
        return $this->type === MillSubmissionType::Edit;
    }

    public function isPending(): bool
    {
        return $this->status === MillSubmissionStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === MillSubmissionStatus::Approved;
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }
}
