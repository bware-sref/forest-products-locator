<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Mill extends Model
{
    /** @use HasFactory<\Database\Factories\MillFactory> */
    use HasFactory;

    /**
     * These model attributes are mass assignable.
     * 
     * @var list<string>
     */
    protected $fillable = [
        'match_id',
        'mill_id',
        'mill_name',
        'latitude',
        'longitude',
        'year',
        'physical_address',
        'physical_city',
        // need to rename county to prevent conflict with relationship field
        'county_name',
        'physical_state',
        'physical_zip',
        'mailing_address',
        'mailing_city',
        'mailing_state',
        'mailing_zip',
        'telephone',
        'fax',
        'type',
        'species',
        'email',
        'web_site',
        'size',
        'modification_date',
        // foreign keys
        'state_id',
        'county_id',
    ];

    /**
     * List of accessors to append to the model's array/JSON form.
     * Accessors with the same name as the underlying attribute do not need to be appended.
     * 
     * @var array
     */
    protected $appends = [
        'physical_address_two',
    ];

    /**
     * Accessors for physical and mailing address
     */
    protected function physicalAddressTwo(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => 
                sprintf(
                    '%s, %s %s',
                    $attributes['physical_city'] ?? '',
                    $attributes['physical_state'] ?? '',
                    $attributes['physical_zip'] ?? ''
                ),
        );
    }

    // belongsTo State
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    // belongsTo County
    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    // hasMany MillTypes
    public function millTypes(): BelongsToMany
    {
        return $this->belongsToMany(MillType::class);
    }

    // hasMany Species
    public function woodSpecies(): BelongsToMany
    {
        return $this->belongsToMany(WoodSpecies::class);
    }
}
