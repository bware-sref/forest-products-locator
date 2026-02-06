<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * TypeScript attribute marks data objects for transformation to TypeScript type for front-end
 *
 * @property int $id
 * @property string $match_id
 * @property string|null $mill_id
 * @property string|null $mill_name
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $year
 * @property string|null $physical_address
 * @property string|null $physical_city
 * @property string|null $county_name
 * @property int|null $county_id
 * @property string|null $physical_state
 * @property int|null $state_id
 * @property string|null $physical_zip
 * @property string|null $mailing_address
 * @property string|null $mailing_city
 * @property string|null $mailing_state
 * @property string|null $mailing_zip
 * @property string|null $telephone
 * @property string|null $fax
 * @property string|null $type
 * @property string|null $species
 * @property string|null $email
 * @property string|null $web_site
 * @property string|null $size
 * @property string|null $modification_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\County|null $county
 * @property-read mixed $mailing_address_two
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MillType> $millTypes
 * @property-read int|null $mill_types_count
 * @property-read mixed $physical_address_two
 * @property-read \App\Models\State|null $state
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WoodSpecies> $woodSpecies
 * @property-read int|null $wood_species_count
 * @method static \Database\Factories\MillFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereCountyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereCountyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereFax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereMailingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereMailingCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereMailingState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereMailingZip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereMatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereMillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereMillName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereModificationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill wherePhysicalAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill wherePhysicalCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill wherePhysicalState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill wherePhysicalZip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereSpecies($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereTelephone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereWebSite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mill whereYear($value)
 * @mixin \Eloquent
 */
#[TypeScript]
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
                self::buildAddress(
                    $attributes['physical_city'],
                    $attributes['physical_state'],
                    $attributes['physical_zip']
                )
        );
    }

    protected function mailingAddressTwo(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => 
                self::buildAddress(
                    $attributes['mailing_city'],
                    $attributes['mailing_state'],
                    $attributes['mailing_zip']
                )
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

    protected static function buildAddress(string $city = '', string $state = '', string $zip = ''): string
    {
        $address = sprintf(
            '%s, %s  %s',
            $city,
            $state,
            $zip
        );

        return trim($address, " \n\r\t\v\0,");
    }
}
