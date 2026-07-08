<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Enums\PublicationStatus;
use App\Helpers\Geo;
use App\Models\Scopes\ApprovedScope;
use App\Models\State;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
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
 * @mixin IdeHelperMill
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
        // even more foreign keys!
        'import_id',
        'user_id',

        /**
         * raw address fields to preserve pre-normalized addresses
         */
        'raw_physical_address',
        'raw_mailing_address',

        /**
         * new fields to handle user submitted mills
         * I think we're moving these to another table, mill_edits maybe?
         */
        'status',
        'submitter_email',
        'submitter_ip',
        'approve_hash',
        'reject_hash',
        'reviewed_at',

    ];

    /**
     * Keys correspond to spreadsheet headings
     * Values are DB column names
     * zOMG, this is too specific.
     * Also, we nixed match_id and mill_id because they're only meaningful in our system.
     * And lastly, I think county => county_name is the only deviation from actual DB field names.
     */
    public const IMPORT_COLUMNS = [
        /**
         * match_id and mill_id have been removed from import configs
         */
        // "match_id" => "match_id",
        // "mill_id" => "mill_id",
        "mill_name" => "mill_name",
        "latitude" => "latitude",
        "longitude" => "longitude",
        "year" => "year",
        /**
         * I don't remember how this works.
         * I think that we need to change the values for physical_address and mailing_address to point at the raw fields
         * if those are the fields where we want to store the unaltered imported address data
         */
        // "physical_address" => "physical_address",
        "physical_address" => "raw_physical_address",
        "physical_city" => "physical_city",
        "county" => "county_name",
        "physical_state" => "physical_state",
        "physical_zip" => "physical_zip",
        // "mailing_address" => "mailing_address",
        "mailing_address" => "raw_mailing_address",
        "mailing_city" => "mailing_city",
        "mailing_state" => "mailing_state",
        "mailing_zip" => "mailing_zip",
        "telephone" => "telephone",
        "fax" => "fax",
        "type" => "type",
        "species" => "species",
        "email" => "email",
        "web_site" => "web_site",
        "size" => "size",
        "modification_date" => "modification_date",

        /**
         * Raw address fields should be used above instead of the actual fields.
         */
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

    protected $casts = [
        'status' => PublicationStatus::class,
    ];

    /**
     * Register event handlers in booted
     */
    protected static function booted(): void
    {
        /**
         * I kinda think this should go on saving...but then again we shouldn't need it except when creating
         * either way, kill the Log output.
         */
        static::creating(function (Mill $mill) {
            /**
             * Now that I think about it, match_id should never be populated here (except maybe when created by a Factory)
             */
            if (!empty($mill->match_id)) {
                // Log::debug("Mill '$mill->mill_name' already has match_id: '$mill->match_id'");
                return;
            }
            $mill->match_id = Mill::makeMatchId($mill);

            // Log::debug("Made match_id '$mill->match_id' for '$mill->mill_name'.");
        });
    }

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

    /**
     * I don't think we actually use this because so far we haven't been given data with mailing address county in it
     * 
     */
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

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * helper to jam together the second line of addresses
     */
    protected static function buildAddress(string $city = '', string $state = '', string $zip = ''): string
    {
        $address = \sprintf(
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
         * We need to exclude Mills which have mills.state_id === null (or empty) because they break MillResources logic.
         * Actually, I fixed the MillResources logic. However, excluding mills without state_id is probably a good thing
         * because it means that Mills that haven't been fully processed would automagically be excluded from search results.
         * However, we can do that later, perhaps with a scope...
         */

        /**
         * Need to add query parameters for specifying a search location and search radius
         * Okay.
         * Proximity fields are only used if all are present.
         * Arguably this should be first (or near first) because it (usually) limits the mills significantly.
         */
        if (!empty($validated['lat']) && !empty($validated['lng']) && !empty($validated['radius'])) {
            /**
             * longitudeDistanceInMilesAtLatitude = 69.17 miles * cos(latitude)
             * make local variables for readability
             */
            $latitude = $validated['lat'];
            $longitude = $validated['lng'];
            $radius = $validated['radius'];
            
            $latitudeRadius = Geo::distanceToDegreesLatitude($radius);
            $longitudeRadius = Geo::distanceToDegreesLongitude($radius, $latitude);
            Log::debug('proximity params in API request: ', ['lng' => $validated['lng'], 'lat' => $validated['lat']]);
            Log::debug(\sprintf('radius %s miles at latitude %f =~ %f degrees longitude', $radius, $latitude, $longitudeRadius));

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
            Log::debug("found mills in session cache!", ['count' => \count($mills)]);
            return $mills;
        }

        if (session()->has('mills')) {
            $mills = session()->get('mills');
            Log::debug('found mills in session', ['count' => \count($mills)]);
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
     * Do we need to remove the Global ApprovedScope for pending() to work right?
     */

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->withoutGlobalScope(ApprovedScope::class)
            ->where('status', PublicationStatus::Pending);
    }

    #[Scope]
    protected function rejected(Builder $query): void
    {
        $query->withoutGlobalScope(ApprovedScope::class)
            ->where('status', PublicationStatus::Rejected);
    }

    /**
     * Methods for admin statistics
     * - mill count, total and by state
     * - number and % of mill listing updates, total and by state, (last week, last month, last 3 months, last 12 months)
     * - number and % of new mill listings, total and by state, last week, month, 3 months, 12 months
     */
    public static function countAll(): int
    {
        return Mill::all()->count();
    }

    public static function counts(): array
    {
        // total is easy
        // by state is also easy
        return [
            'total' => Mill::countAll(),
            'byState' => State::millCounts(),
        ];
    }

    // public static function updates(): array
    // {
    //     /**
    //      * Updates are a little messier.
    //      * number and percentage updated, total and by state, timeframes
    //      * 
    //      */

    //     $timeframes = self::getTimeframes();

    //     $data = [];
        
    //     /**
    //      * We only need to count all mills once.
    //      * Now think of a good way to hoist doing that to the level we need.
    //      * That probably means passing $millCount to this function.
    //      */
    //     $millCount = Mill::all()->count();

    //     foreach ($timeframes as $key => $tf) {
    //         $block = [];
    //         $updated = Mill::updatedSince($tf);
    //         $block['total']['number'] = $updated;
    //         $block['total']['percentage'] = ($updated / $millCount) * 100;

    //         $block['byState'] = [];
    //         $block['since'] = $tf->toDateTimeString();
    //         $data[$key] = $block;
    //     }

    //     return $data;
    // }

    // public static function additions(): array
    // {
    //     return self::sinceTimeframes('created');
    // } 

    // protected static function sinceTimeframes(string $action = 'updated'): array
    // {
    //     $timeframes = self::getTimeframes();

    //     $data = [];

    //     $millCount = Mill::countAll();

    //     foreach ($timeframes as $key => $tf) {
    //         $block = [];
    //         $updated = Mill::updatedSince($tf);
    //         $block['total']['number'] = $updated;
    //         $block['total']['percentage'] = ($updated / $millCount) * 100;

    //         $block['byState'] = [];
    //         $block['since'] = $tf->toDateTimeString();
    //         $data[$key] = $block;
    //     }

    //     return $data;
    // }

    public static function createdSince(Carbon $since): int
    {
        return self::changedSince($since, 'created');
    }

    public static function updatedSince(Carbon $since): int
    {
        return self::changedSince($since, 'updated');
    }

    protected static function changedSince(Carbon $since, string $column = 'updated'): int
    {
        $column = ('updated' === $column ? $column : 'created') . '_at';

        $howMany = Mill::select('match_id')
            ->where($column, '>=', $since)
            ->get()
            ->count();

        return $howMany;
    }

    protected static function getTimeframes(): array
    {
        return [
            'lastWeek' => Carbon::now()->minus(weeks: 1),
            'lastMonth' => Carbon::now()->minus(months: 1),
            'lastThreeMonths' => Carbon::now()->minus(months: 3),
            'lastYear' => Carbon::now()->minus(years: 1),
        ];
    }

    public static function makeMatchId(Mill|array $mill): string
    {
        $mill = \is_array($mill) ? $mill : $mill->toArray();
        /**
         * go with the default matchId at first
         * should probably throw an exception here
         */
        if (empty($mill['mill_name'])) {
            Log::error(self::class.'makeMatchId(): no mill name?!?', ['mill' => $mill]);
            // dd($mill);
            // throw new exception
        }
        $slug = Str::slug($mill['mill_name']);
        $slugWithCity = Str::slug($mill['mill_name'] . ' ' . $mill['physical_city']);

        /**
         * see if there's an exact match
         */
        $others = Mill::select('match_id')
            ->where('match_id', $slug)
            ->orWhere('match_id', $slugWithCity)
            ->orWhereLike('match_id', "$slug%", caseSensitive: false)
            ->orWhereLike('match_id', "$slugWithCity%", false)
            ->orderBy('match_id','desc')
            ->get()
            ->withoutAppends()
            ->pluck('match_id');

        /**
         * if not, go with the simplest version
         */
        if (empty($others)) {
            return $slug;
        }

        /**
         * here's where things get interesting and/or annoying.
         * we have to make something unique...easiest way is to append a number instead of doing something more meaningful.
         * in that case, we could change our earlier query to find Mills
         * with match_id LIKE 'simple%' and order them by match_id descending so that we only get the latest (or the one that sorts last)
         * now isolate the suffix by replacing $matchId in the fetched row.
         * then what?
         * Well now.
         * They're not all numeric.
         * Maybe we should look for both the mill name slug and the mill slug
         * followed by city name
         */
        /**
         * Next simplest version is appending the city
         */
        // if (!in_array($slugWithCity, $others->toArray())) { //$others->match_id !== $slugWithCity) {
        if (!$others->contains($slugWithCity)) {
            return $slugWithCity;
        }

        /**
         * since we've determined slugWithCity is already in there, we can use it as the basis for this slug
         * but we need to find the last one that includes $slugWithCity
         * then add a suffix to that one
         */
        $latest = $others->filter(function ($item) use ($slugWithCity) {
            return Str::startsWith($item, $slugWithCity);
        })->sortDesc()->first();

        $suffix = Str::ltrim(Str::remove($slugWithCity, $latest), '-_ ');

        /**
         * if the suffix is numeric, increment it and bail.
         * if not, make a numeric suffix
         */
        $suffix = is_numeric($suffix) ? (int) $suffix : 0;
        $suffix += 1;
        
        return "$slugWithCity-$suffix";
    }

    /**
     * Join all portions of an address into a single string.
     * Used for geocoding.
     * 
     * @param mixed $type = 'physical'
     * @return string
     */
    public function getRawAddress(?string $type = 'physical'): string
    {
        $type = ('mailing' === $type ? $type : 'physical');

        if (!empty($this->{"raw_{$type}_address"})) {
            return $this->{"raw_{$type}_address"};
        }

        $fields = [
            $this->{"{$type}_address"} ?? '',
            $this->{"{$type}_city"} ?? '',
            $this->{"{$type}_state"} ?? '',
            $this->{"{$type}_zip"} ?? '',
        ];

        return join(' ', $fields);
    }

    public function hasAddress(?string $type = 'physical'): bool
    {
        /**
         * Should we even take raw_physical_address into account here?
         * No.
         * Let getRawAddress() handle it.
         * Also, we don't need to check the value of $type here either, because getRawAddress() will handle it.
         */
        // $type = ('mailing' === $type ? $type : 'physical');
        return ! empty($this->getRawAddress($type));
    }

    public function hasLatLng(): bool
    {
        /**
         * do we actually need to do the value
         */
        $lat = (float) $this->latitude ?? null;
        $lng = (float) $this->longitude ?? null;
        if (empty($lat) || $lat > 90 || $lat < -90 ||
            empty($lng) || $lng > 180 || $lat < -180
        ) {
            return false;
        }
        return true;
    }

    /**
     * Returns an array containing the longitude and latitude values for this Mill.
     * The array is keyed by the full field names and ordered so that longitude is first to accomodate geocoding.
     * @return array{longitude: string|null, latitude: string|null}
     */
    public function lngLat(): array
    {
        return [
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
        ];
    }

    /**
     * map type to mill_types
     * maybe this is better for MillTypes to handle?
     * maybe, but we can at least make it easy to explode the raw data into a list
     */
    public function getRawTypeList(): array
    {
        return $this->getRawList('type');
    }

    /**
     * same for wood species
     */
    public function getRawSpeciesList(): array
    {
        return $this->getRawList('species');
    }

    protected function getRawList(string $field = 'type'): array
    {
        /**
         * how do we limit only to a few fields?
         * same as mailing and physical: make the typical the default value, then assert against the other value
         * or else just make an array to check against
         */
        $allowedFields = [
            'type',
            'species'
        ];

        /**
         * If this isn't a valid field, just bail silently and without error.
         * Maybe log it :-D
         */
        if (! \in_array($field, $allowedFields)) {
            Log::error(self::class."::getRawList(): invalid field name '{$field}'. Failing silently...");
            return [];
        }

        /**
         * get the field
         * if empty return an empty array
         */
        $value = Str::trim($this->$field);

        /**
         * get the separator, if any
         */
        $separators = ['|', ','];
        $separator = null;        
        foreach ($separators as $sep) {
            if (Str::contains($value, $sep)) {
                $separator = $sep;
                break;
            }
        }

        /**
         * if we found a separator, explode on it and return
         */
        if (!empty($separator)) {
            return array_map(
                fn ($item) => Str::trim($item),
                explode($separator, $value)
            );
        }

        /**
         * if no separator, return the field in an array.
         */
        return [$value];
    }

    public static function deleteOldImports(int $importId, int $stateId): int
    {
        /**
         * @suppress PHP1005
         * well that didn't phucking work
         * back to adding optional parameters that already have default values that aren't properly identified by one of the damn 
         * things: Intelephense, Laravel VS Code extension, or barryvhd/ide-helper
         */
        return Mill::whereNotNull('import_id', boolean: 'and')
            ->whereNot('import_id', $importId)
            ->where('state_id', $stateId)
            ->delete();
    }

    public static function publishFromImport(int $importId): bool
    {
        /**
         * Holy mole!
         * After a bunch of BS, going back to using query() first made Intelephense happy.
         */
        return Mill::query()->pending()
            ->where('import_id', $importId)
            ->update([
                'status' => PublicationStatus::Approved,
            ]);
    }
}
