<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Models\State;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $state_id
 * @property string $title
 * @property string|null $content
 * @property int $sort_weight
 * @property PublicationStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read State|null $state
 * @method static \Database\Factories\StateResourceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource whereSortWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateResource whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StateResource extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StateResourceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'state_id',
        'title',
        'content',
        'sort_weight',
        'status',
        'created_at',
        'updated_at',        
    ];

    /**
     * Cast status to a BackedEnum so we can use it more easily with Backpack
     */
    protected $casts = [
        'status' => PublicationStatus::class,
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
