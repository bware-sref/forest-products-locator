<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $type
 * @property string|null $full_name
 * @property string|null $county_code
 * @property string|null $state_code
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $geo_shape
 * @property string|null $fips_code
 * @property string|null $gnis_code
 * @property int $state_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mill> $mills
 * @property-read int|null $mills_count
 * @property-read \App\Models\State $state
 * @method static \Database\Factories\CountyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereCountyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereFipsCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereGeoShape($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereGnisCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereStateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|County whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class County extends Model
{
    /** @use HasFactory<\Database\Factories\CountyFactory> */
    use HasFactory;


    protected $fillable = [
        'name', // e.g., Clarke
        'type', // e.g., county, parish, et al
        'full_name', // e.g., Clarke County (this might end up as an accessor)
        'latitude',
        'longitude',
        'geo_shape',
        // relics of the data set
        'county_code',
        // Federal Information Processing Standards
        'fips_code',
        // Geographic Names Information System
        'gnis_code',
        // this is state_id local to our DB
        'state_id',
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
     * County hasMany Mills
     */
    public function mills(): HasMany
    {
        return $this->hasMany(Mill::class);
    }

    /**
     * County belongsTo State
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
