<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperStateAssistanceCategory
 */
class StateAssistanceCategory extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StateAssistanceCategoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'state_id',
        'title',
        'description',
        'sort_weight',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(StateAssistanceLink::class)
            ->orderBy('sort_weight', 'asc');
    }
}
