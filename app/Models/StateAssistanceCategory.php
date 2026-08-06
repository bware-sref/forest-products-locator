<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * "{State} — {Category}" label so StateAssistanceLink's category select
     * (list column and create/update field) is self-describing -- links
     * have no direct state_id of their own, so without this it's impossible
     * to tell which state you're editing from that screen alone.
     */
    protected function selectLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(($this->state?->name ? $this->state->name.' — ' : '').$this->title),
        );
    }
}
