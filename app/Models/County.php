<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
