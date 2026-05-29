<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $polygon
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\County> $counties
 * @property-read int|null $counties_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mill> $mills
 * @property-read int|null $mills_count
 * @method static \Database\Factories\StateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereAbbreviation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State wherePolygon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Agent> $agents
 * @property-read int|null $agents_count
 * @property-read mixed $label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mill> $mailingMills
 * @property-read int|null $mailing_mills_count
 * @property-read mixed $value
 * @mixin \Eloquent
 */
class State extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StateFactory> */
    use HasFactory;

    /**
     * List of southern state abbreviations.
     * These are our primary area of focus, though we are open to expanding to other states in the future.
     */
    public static $southernStates = [
        'AL',
        'AR',
        'FL',
        'GA',
        'KY',
        'LA',
        'MS',
        'NC',
        'OK',
        'SC',
        'TN',
        'TX',
        'VA',
    ];

    protected $fillable = [
        'name',
        'abbreviation',
        'latitude',
        'longitude',
        'polygon',
    ];

    /**
     * add attributes to facilitate use with select option values
     */
    protected $appends = [
        'value',
        'label',
    ];

    /**
     * Attribute Accessors
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            // casting the value attribute to a string here might have saved us some headache earlier on
            // except normalizeStates broke when I removed the string cast it contained/s
            get: fn () => (string) $this->id,
        );
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->name,
        );
    }

    /**
     * Relationships
     */
    // hasMany Counties
    public function counties(): HasMany
    {
        return $this->hasMany(County::class);
    }

    // hasMany Mills
    public function mills(): HasMany
    {
        return $this->hasMany(Mill::class);
    }

    // inverse of mailingState relationship
    public function mailingMills(): HasMany
    {
        return $this->hasMany(Mill::class, 'mailing_state_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    /**
     * Simple-ish way to query mill types by State via basic Eloquent.
     * It still might be more managable to just install the package that adds deep relationships.
     */
    public function millTypes(?int $stateId = null): Collection
    {
        $stateId ??= $this->id;
        $millTypes = DB::table('mill_types')
            ->join('mill_mill_type', 'mill_types.id', '=', 'mill_mill_type.mill_type_id')
            ->whereIn('mill_mill_type.mill_id', function ($query) use ($stateId) {
                $query->select('id')
                    ->from('mills')
                    ->where('mills.state_id', $stateId);
            })
            ->select('mill_types.id', 'mill_types.name')
            ->distinct()
            ->get();
        return $millTypes;
    }


    /**
     * Simple-ish way to query woodSpecies by State via Eloquent.
     */
    public function woodSpecies(?int $stateId = null): Collection
    {
        $stateId ??= $this->id;
        $woodSpecies = DB::table('wood_species')
            ->join('mill_wood_species', 'wood_species.id', '=', 'mill_wood_species.wood_species_id')
            ->whereIn('mill_wood_species.mill_id', function ($query) use ($stateId) {
                $query->select('id')
                    ->from('mills')
                    ->where('mills.state_id', $stateId);
            })
            ->select('wood_species.id', 'wood_species.name')
            ->distinct()
            ->get();
        return $woodSpecies;
    }

    /**
     * Query helper methods
     */

    /**
     * getMillStates()
     * Fetches States which have Mills along with their Counties (which have Mills)
     * The latter portions may end up being optional...via options!
     * 
     */
    public static function getMillStates(?bool $withCounties = true): Collection
    {
        $states = State::has('mills');
        if ($withCounties) {
            $states->with([
                'counties' => function ($query) {
                    $query->select('id', 'name', 'state_id')
                        ->has('mills')
                        ->orderBy('name', 'asc');
            }]);
        }
        return $states->get(['id', 'name', 'abbreviation'])
            ->append(['value', 'label']);
    }

    /**
     * getWithCounties()
      * Fetches States along with their Counties.
      * Both States and Counties can be filtered by passing an array of columns to select.
      * By default, all columns are selected for both States and Counties.
      * The $keyForSelect parameter determines whether to append 'value' and 'label' attributes to the State models, which can be useful for select inputs in the frontend.
      * By default, $keyForSelect is true, meaning 'value' and 'label' will be appended to the State models. If set to false, these attributes will not be appended.
     * This method is primarily intended for fetching States and their Counties for use in select inputs, but it can be used in other contexts as well.
     */
    public static function getWithCounties(?array $cols = ['*'], ?array $countyCols = ['*'], ?bool $keyForSelect = true): Collection
    {
        $states = State::select($cols)
            ->with([
                'counties' => function ($query) use ($countyCols) {
                    $query->select($countyCols)
                        ->orderBy('name', 'asc');
            }])->get();
        
        if ($keyForSelect) {
            $states->append(['value', 'label']);
        }
        return $states;
    }
 
    public static function millCounts(): array
    {
        return State::select('name')
            ->has('mills')
            ->withCount('mills')
            ->get()
            ->withoutAppends()
            ->pluck('mills_count', 'name')
            // ->keyBy('name')
            ->toArray();
    }

    public static function millsCreatedSince(Carbon $since): array
    {
        return self::millsChangedSince($since, 'created');
    }

    public static function millsUpdatedSince(Carbon $since): array
    {
        return self::millsChangedSince($since, 'updated');
    }

    protected static function millsChangedSince(Carbon $since, string $action = 'updated'): array
    {
        $column = ('updated' === $action ? $action : 'created') . '_at';

        /**
         * I think I've been going about this all wrong.
         * In addition to passing a query to whereHas(), we need to pass an array to withCount() where relationship names key the queries.
         */
        $changed = State::select('name')
            ->whereHas('mills', function ($query) use ($column, $since) {
                $query->where($column, '>=', $since);
            })
            ->withCount(['mills' => function ($query) use ($column, $since) {
                $query->where($column, '>=', $since);
            }])            
            ->get()
            ->withoutAppends()
            ->pluck('mills_count', 'name')
            ->toArray();

        return $changed;
    }
}
