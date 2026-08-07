<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperStateForestryAgency
 */
class StateForestryAgency extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StateForestryAgencyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'state_id',
        'headline',
        'body',
        'cta_1_label',
        'cta_1_url',
        'cta_2_label',
        'cta_2_url',
        'assistance_headline',
        'assistance_copy',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function assistanceCategories(): HasMany
    {
        return $this->hasMany(StateAssistanceCategory::class, 'state_id', 'state_id')
            ->orderBy('sort_weight', 'asc');
    }
}
