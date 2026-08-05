<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
// use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * @mixin IdeHelperState
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
        'slug',
        'abbreviation',
        'latitude',
        'longitude',
        'polygon',
        'resource_summary',
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

    // public function agents(): HasMany
    // {
    //     return $this->hasMany(Agent::class);
    // }

    public function stateResources(): HasMany
    {
        return $this->hasMany(StateResource::class)
            ->orderBy('sort_weight', 'asc');
    }

    /**
     * State page content.
     * All page-section models below hang directly off state_id (siblings of
     * StatePage) rather than nesting under StatePage's id, matching the
     * pattern already established by stateContacts().
     */
    public function statePage(): HasOne
    {
        return $this->hasOne(StatePage::class);
    }

    public function stateContacts(): HasMany
    {
        return $this->hasMany(StateContact::class)
            ->orderBy('sort_weight', 'asc');
    }

    public function stateForestOverview(): HasOne
    {
        return $this->hasOne(StateForestOverview::class);
    }

    public function stateForestTypes(): HasMany
    {
        return $this->hasMany(StateForestType::class)
            ->orderBy('sort_weight', 'asc');
    }

    public function stateForestProducts(): HasMany
    {
        return $this->hasMany(StateForestProduct::class)
            ->orderBy('sort_weight', 'asc');
    }

    public function stateEconomicImpact(): HasOne
    {
        return $this->hasOne(StateEconomicImpact::class);
    }

    public function stateForestryAgency(): HasOne
    {
        return $this->hasOne(StateForestryAgency::class);
    }

    public function stateAssistanceCategories(): HasMany
    {
        return $this->hasMany(StateAssistanceCategory::class)
            ->orderBy('sort_weight', 'asc');
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

    public static function byNameOrAbbreviation(string $name, ?string $countyName = null): ?State
    {
        $query = State::select(['id', 'name'])
            ->where('abbreviation', $name, null, 'and')
            ->orWhere('name', $name);
        
        if (null !== $countyName) {
            $query = $query->with([
                'counties' => function ($q) use ($countyName) {
                    $q->select(['id', 'name', 'type', 'state_id'])                        
                        ->where('name', $countyName);
                }
            ]);
        }

        return $query->first()?->withoutAppends();
    }
}
