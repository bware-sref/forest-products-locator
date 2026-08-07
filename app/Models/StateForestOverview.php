<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperStateForestOverview
 */
class StateForestOverview extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StateForestOverviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'state_id',
        'headline',
        'body',
        'image',
        'stat_1_label',
        'stat_1_value',
        'stat_2_label',
        'stat_2_value',
        'stat_3_label',
        'stat_3_value',
        'stat_4_label',
        'stat_4_value',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function forestTypes(): HasMany
    {
        return $this->hasMany(StateForestType::class, 'state_id', 'state_id')
            ->orderBy('sort_weight', 'asc');
    }

    public function forestProducts(): HasMany
    {
        return $this->hasMany(StateForestProduct::class, 'state_id', 'state_id')
            ->orderBy('sort_weight', 'asc');
    }
}
