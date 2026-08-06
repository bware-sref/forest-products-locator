<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperStateAssistanceLink
 */
class StateAssistanceLink extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StateAssistanceLinkFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'state_assistance_category_id',
        'label',
        'description',
        'url',
        'sort_weight',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StateAssistanceCategory::class, 'state_assistance_category_id');
    }

    /**
     * Links have no state_id of their own -- only a category, which itself
     * belongs to a state. Used for the "State" list column so it's obvious
     * which state a given link belongs to without opening the category.
     */
    public function stateName(): ?string
    {
        return $this->category?->state?->name;
    }
}
