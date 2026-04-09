<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agent extends Model
{
    /** @use HasFactory<\Database\Factories\AgentFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'title',
        'email',
        'phone',
        'state_id',
        'street_address',
        'city',
        'zip_code',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
