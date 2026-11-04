<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One state's mill statistics on one day. See App\Statistics\Snapshots.
 *
 * metrics: {mills: int, complete: int, fields: {email: int, ...}, types: {"Sawmill": int, ...}}
 */
class StatisticsSnapshot extends Model
{
    protected $fillable = ['captured_on', 'state_id', 'metrics'];

    protected function casts(): array
    {
        return [
            'captured_on' => 'date',
            'metrics' => 'array',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
