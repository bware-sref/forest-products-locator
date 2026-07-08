<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAgent
 */
class Agent extends Model
{
    use CrudTrait;
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

    /**
     * StateAgents also have a User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
