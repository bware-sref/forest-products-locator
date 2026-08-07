<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperStateForestType
 */
class StateForestType extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StateForestTypeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'state_id',
        'title',
        'description',
        'icon',
        'sort_weight',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
