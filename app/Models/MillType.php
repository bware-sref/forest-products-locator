<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mill> $mills
 * @property-read int|null $mills_count
 * @method static \Database\Factories\MillTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MillType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MillType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MillType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MillType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MillType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MillType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MillType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MillType extends Model
{
    /** @use HasFactory<\Database\Factories\MillTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * add attributes to facilitate use with option values
     */
    protected $appends = ['value', 'label'];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->id,
        );
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->name,
        );
    }


    /**
     * MillType belongs to many Mills
     */
    public function mills(): BelongsToMany
    {
        return $this->belongsToMany(Mill::class);
    }

}
