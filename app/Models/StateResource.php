<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Models\State;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StateResource extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StateResourceFactory> */
    use HasFactory;

    protected array $fillable = [
        'state_id',
        'title',
        'content',
        'sort_weight',
        'status',
        'created_at',
        'updated_at',        
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
