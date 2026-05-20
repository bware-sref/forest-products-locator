<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Enums\PublicationStatus;
use App\Helpers\Geo;
use App\Models\Scopes\ApprovedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\hasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;
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
 * @property int|null $mailing_state_id
 * @property int|null $mailing_county_id
 * @property string $status
 * @property string|null $submitter_email
 * @property string|null $submitter_ip
 * @property string|null $approve_hash
 * @property string|null $reject_hash
 * @property string|null $reviewed_at
 * @property-read \App\Models\County|null $mailingCounty
 * @property-read \App\Models\State|null $mailingState
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MillEdit> $millEdits
 * @property-read int|null $mill_edits_count
 * @method static Builder<static>|Mill pending()
 * @method static Builder<static>|Mill rejected()
 * @method static Builder<static>|Mill whereApproveHash($value)
 * @method static Builder<static>|Mill whereMailingCountyId($value)
 * @method static Builder<static>|Mill whereMailingStateId($value)
 * @method static Builder<static>|Mill whereRejectHash($value)
 * @method static Builder<static>|Mill whereReviewedAt($value)
 * @method static Builder<static>|Mill whereStatus($value)
 * @method static Builder<static>|Mill whereSubmitterEmail($value)
 * @method static Builder<static>|Mill whereSubmitterIp($value)
 * @mixin \Eloquent
 */
#[TypeScript]
#[ScopedBy([ApprovedScope::class])]
class Mill extends Model
{
    use CrudTrait;
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
        // more foreign keys!
        'mailing_state_id',
        'mailing_county_id',

        /**
         * new fields to handle user submitted mills
         */
        'status',
        'submitter_email',
        'submitter_ip',
        'approve_hash',
        'reject_hash',
        'reviewed_at',

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
                    $attributes['physical_city'] ?? '',
                    // hopefully averting an undefined array key error
                    $attributes['physical_state'] ?? '',
                    $attributes['physical_zip'] ?? ''
                )
        );
    }

    protected function mailingAddressTwo(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => 
                self::buildAddress(
                    $attributes['mailing_city'] ?? '',
                    $attributes['mailing_state'] ?? '',
                    $attributes['mailing_zip'] ?? ''
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

    /**
     * @tada: add mailingState and mailingCounty relationships
     */
    public function mailingState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'mailing_state_id');
    }

    public function mailingCounty(): BelongsTo
    {
        return $this->belongsTo(County::class, 'mailing_county_id');
    }

    // belongsToMany MillTypes
    public function millTypes(): BelongsToMany
    {
        return $this->belongsToMany(MillType::class);
    }

    // belongsToMany WoodSpecies
    public function woodSpecies(): BelongsToMany
    {
        return $this->belongsToMany(WoodSpecies::class);
    }

    // hasMany MillEdits
    public function millEdits(): hasMany
    {
        return $this->hasMany(MillEdit::class);
    }


    /**
     * helper to jam together the second line of addresses
     */
    protected static function buildAddress(string $city = '', string $state = '', string $zip = ''): string
    {
        $address = sprintf(
            '%s, %s  %s',
            $city,
            $state,
            $zip
        );

        // note the comma among the trimmed characters
        // it handles the case of empty city values
        return trim($address, " \n\r\t\v\0,");
    }

    /**
     * creates and executes a query based on the pre-validated API parameters
     */
    public static function apiSearch($validated = [])
    {
        /**
         * we need to check for the following:
         * and I almost forgot that everything except mill_name needs to query a relationship
         * - q
         * - millType
         * - woodSpecies
         * - state
         * - county
         * Also!
         * Need to incorporate location!
         */
        $query = Mill::with(['millTypes', 'woodSpecies', 'state', 'county']);

        /**
         * Need to add query parameters for specifying a search location and search radius
         * Okay.
         * Proximity fields are only used if all are present.
         * Arguably this should be first (or near first) because it (usually) limits the mills significantly.
         */
        if (!empty($validated['y']) && !empty($validated['x']) && !empty($validated['radius'])) {
            /**
             * longitudeDistanceInMilesAtLatitude = 69.17 miles * cos(latitude)
             * make local variables for readability
             */
            $latitude = $validated['y'];
            $longitude = $validated['x'];
            $radius = $validated['radius'];
            
            $latitudeRadius = Geo::distanceToDegreesLatitude($radius);
            $longitudeRadius = Geo::distanceToDegreesLongitude($radius, $latitude);
            Log::debug('proximity params in API request: ', ['x' => $validated['x'], 'y' => $validated['y']]);
            Log::debug(sprintf('radius %s miles at latitude %f =~ %f degrees longitude', $radius, $latitude, $longitudeRadius));

            $query->whereRaw('(
                CAST(latitude AS DECIMAL) <= (? + ?) AND 
                CAST(latitude AS DECIMAL) >= (? - ?) AND
                CAST(longitude AS DECIMAL) <= (? + ?) AND 
                CAST(longitude AS DECIMAL) >= (? - ?)
            )', [
                $latitude,
                $latitudeRadius,
                $latitude,
                $latitudeRadius,
                $longitude,
                $longitudeRadius,
                $longitude,
                $longitudeRadius,
            ]);
        }

        // add match_id to the fields that get compared to q
        if (!empty($validated['q'])) {
            /**
             * This might need to be whereAny() instead of whereLike() and orWhereLike()...
             * Yes, that seems to be the case.
             * whereAny() prevents the grouping issue introduced by orWhereLike().
             * ...
             * Well shit.
             * I should probably add more fields to this query so we search everything...
             * But is that useful?
             */
            $query->whereAny([
                'mill_name',
                'match_id',
            ], 'like', '%' . $validated['q'] . '%');
            // $query->whereLike('mill_name', '%' . $validated['q'] . '%')
            //     ->orWhereLike('match_id', '%' .$validated['q'] . '%');
        }

        if (!empty($validated['millType'])) {
            $query->whereHas('millTypes', function (Builder $query) use ($validated) {
                // update to use id instead of name
                // prefix id with DB table name to disambiguate!
                $query->where('mill_types.id', $validated['millType']);
            });
        }

        if (!empty($validated['woodSpecies'])) {
            $query->whereHas('woodSpecies', function (Builder $query) use ($validated) {
                // update to use id instead of name
                $query->where('wood_species.id', $validated['woodSpecies']);
            });
        }

        if (!empty($validated['state'])) {
            /**
             * Trying to remember why I used whereHas() for state instead of just checking the mill.state_id...
             * Is it because I want to return the state as well?
             * In any case, where mill.state_id seems to work the same.
             * That said, keep an eye out for side-effects related to this change.
             */
            $query->where('state_id', $validated['state']);
            // $query->whereHas('state', function (Builder $query) use ($validated) {
            //     // update to use id instead of abbreviation
            //     // $query->where('abbreviation', $validated['state']);
            //     $query->where('states.id', $validated['state']);
            // });
        }

        if (!empty($validated['county'])) {
            /**
             * If it works for state, probably also works for county...
             * Again, keep an eye out for side-effects.
             * As an aside, neither where() nor whereHas() seems to have an impact on the counties listed in the County dropdown.
             */
            $query->where('county_id', $validated['county']);
            // $query->whereHas('county', function (Builder $query) use ($validated) {
            //     // update to use id instead of name
            //     $query->where('counties.id', $validated['county']);
            // });
        }

        return $query->get();
    }

    /**
     * queries Mills for exporting
     */
    public static function fetchForExport()
    {
        /**
         * if there are mills in the session cache, return them
         */
        if (session()->cache()->has('mills')) {
            $mills = session()->cache()->get('mills');
            Log::debug("found mills in session cache!", ['count' => count($mills)]);
            return $mills;
        }

        if (session()->has('mills')) {
            $mills = session()->get('mills');
            Log::debug('found mills in session', ['count' => count($mills)]);
            return $mills;
        }

        Log::debug('no mills in session...');

        /**
         * otherwise, fetch them all
         */
        return Mill::with([
                'state:id,name,abbreviation',
                'county:id,name',
                'millTypes:id,name',
                'woodSpecies:id,name',
            ])->get([
                /**
                 * We may need to revise this list
                 */
                'id',
                'match_id',
                'mill_name',
                'latitude',
                'longitude',
                'year',
                'physical_address',
                'physical_city',
                'county_id',
                'state_id',
                'physical_zip',
                'mailing_address',
                'mailing_city',
                'mailing_county_id',
                'mailing_state_id',
                'mailing_zip',
                'telephone',
                'fax',
                'email',
                'web_site',
                'size',
                'updated_at',
            ]);
    }

    /**
     * Scopes!
     * as indicated by the #[Scope] attribute/decorator
     */

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', PublicationStatus::Pending);
    }

    #[Scope]
    protected function rejected(Builder $query): void
    {
        $query->where('status', PublicationStatus::Rejected);
    }


}
