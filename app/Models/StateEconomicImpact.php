<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperStateEconomicImpact
 */
class StateEconomicImpact extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StateEconomicImpactFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'state_id',
        'headline',
        'stat_1_label',
        'stat_1_value',
        'stat_2_label',
        'stat_2_value',
        'stat_3_label',
        'stat_3_value',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
