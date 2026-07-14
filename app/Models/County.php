<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperCounty
 */
class County extends Model
{
    use CrudTrait;
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
     * Inverse of Mill::mailingCounty()
     */
    public function mailingMills(): HasMany
    {
        return $this->hasMany(Mill::class, 'mailing_county_id');
    }

    /**
     * County belongsTo State
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
