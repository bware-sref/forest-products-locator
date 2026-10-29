<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Helpers\Geo;
use App\Models\County;
use App\Models\Import;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

/**
 * Mills are placed near the centroid of a real county in their state, so the state, county and coordinates agree.
 * Street addresses and zips are fake and won't match the coordinates; that's fine, since GeocodeMill trusts a street
 * address that arrives with coordinates, and tests stub the geocoders anyway.
 *
 * Default: an approved (publicly visible) mill with a full physical address, matching mailing address, and
 * coordinates. See the state methods below for pending/imported/incomplete variants.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mill>
 */
class MillFactory extends Factory
{
    /**
     * How far from the county centroid a mill may land.
     */
    private const JITTER_MILES = 5;

    /**
     * Decoded seed data, loaded once per process.
     *
     * @var array<string, list<array{name: string, latitude: string, longitude: string}>>|null counties by state name
     */
    private static ?array $countiesByState = null;

    /**
     * @var array<string, array{latitude: string, longitude: string}>|null state centers by state name
     */
    private static ?array $stateCenters = null;

    /**
     * Define the model's default state.
     *
     * Closures run in order and see the attributes resolved before them, so a county is picked once (county_name)
     * and the coordinates are then derived from it. Overriding county_name, latitude or longitude skips those steps.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'state_id' => State::factory(),
            'mill_name' => fake()->lastName().' '.fake()->randomElement([
                'Lumber', 'Timber', 'Wood Products', 'Sawmill', 'Forest Products', 'Pallet', 'Hardwoods', 'Pulp & Paper',
            ]).fake()->randomElement(['', '', ' Co.', ' Inc.', ', LLC']),
            'status' => PublicationStatus::Approved,

            'physical_address' => fake()->streetAddress(),
            'physical_city' => fake()->city(),
            'physical_state' => fn (array $attributes) => State::find($attributes['state_id'])?->abbreviation,
            'physical_zip' => fake()->postcode(),
            'county_name' => fn (array $attributes) => self::randomCountyIn($attributes['state_id'])['name'] ?? null,
            'county_id' => fn (array $attributes) => County::where('state_id', $attributes['state_id'])
                ->where('name', $attributes['county_name'])
                ->value('id'),
            'latitude' => fn (array $attributes) => self::pointNear($attributes)['latitude'],
            'longitude' => fn (array $attributes) => self::pointNear($attributes)['longitude'],

            'mailing_address' => fn (array $attributes) => $attributes['physical_address'],
            'mailing_city' => fn (array $attributes) => $attributes['physical_city'],
            'mailing_state' => fn (array $attributes) => $attributes['physical_state'],
            'mailing_zip' => fn (array $attributes) => $attributes['physical_zip'],
            'mailing_state_id' => fn (array $attributes) => $attributes['state_id'],

            'telephone' => fake()->numerify('###-###-####'),
            'email' => fake()->companyEmail(),
            'web_site' => fake()->optional()->url(),
        ];
    }

    /**
     * Awaiting review: hidden from the public site (and from Mill queries, via ApprovedScope).
     */
    public function pending(): static
    {
        return $this->state(['status' => PublicationStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => PublicationStatus::Rejected]);
    }

    public function invalid(): static
    {
        return $this->state(['status' => PublicationStatus::Invalid]);
    }

    /**
     * Freshly imported from a spreadsheet or ArcGIS: pending, in the import's state.
     */
    public function fromImport(Import $import): static
    {
        return $this->pending()->state(fn () => array_filter([
            'import_id' => $import->id,
            'state_id' => $import->state_id,
        ], fn ($value) => null !== $value));
    }

    /**
     * Needs a geocode lookup (address → coordinates).
     */
    public function withoutCoordinates(): static
    {
        return $this->state(['latitude' => null, 'longitude' => null]);
    }

    /**
     * Needs a reverse lookup (coordinates → address), like North Carolina's ArcGIS data: state and coordinates only.
     */
    public function withoutAddress(): static
    {
        return $this->state([
            'physical_address' => null,
            'physical_city' => null,
            'physical_zip' => null,
            'mailing_address' => null,
            'mailing_city' => null,
            'mailing_zip' => null,
        ]);
    }

    /**
     * Neither address nor coordinates: GeocodeMill marks these Invalid.
     */
    public function withoutLocation(): static
    {
        return $this->withoutAddress()->withoutCoordinates()->state(['county_name' => null]);
    }

    /**
     * A random county from the seed data for the given state, or null if the data doesn't cover that state.
     *
     * @return array{name: string, latitude: string, longitude: string}|null
     */
    private static function randomCountyIn(?int $stateId): ?array
    {
        $stateName = State::find($stateId)?->name;
        $counties = self::countiesByState()[$stateName] ?? [];

        return empty($counties) ? null : fake()->randomElement($counties);
    }

    /**
     * A point within JITTER_MILES of the mill's county centroid, else of its state's center.
     *
     * @return array{latitude: float|null, longitude: float|null}
     */
    private static function pointNear(array $attributes): array
    {
        $stateName = State::find($attributes['state_id'] ?? null)?->name;

        $center = collect(self::countiesByState()[$stateName] ?? [])->firstWhere('name', $attributes['county_name'] ?? null)
            ?? self::stateCenters()[$stateName]
            ?? null;

        if (! $center) {
            return ['latitude' => null, 'longitude' => null];
        }

        $latitude = (float) $center['latitude'];
        $longitude = (float) $center['longitude'];
        $offset = fn (float $maxDegrees) => fake()->randomFloat(6, -$maxDegrees, $maxDegrees);

        return [
            'latitude' => round($latitude + $offset(Geo::distanceToDegreesLatitude(self::JITTER_MILES)), 6),
            'longitude' => round($longitude + $offset(Geo::distanceToDegreesLongitude(self::JITTER_MILES, $latitude)), 6),
        ];
    }

    /**
     * Same file CountySeeder uses.
     */
    private static function countiesByState(): array
    {
        return self::$countiesByState ??= collect(
            json_decode(File::get(database_path('data/us-counties-by-state__only-south.json')), true)
        )->groupBy('state_name')->map->all()->all();
    }

    /**
     * Same file StateSeeder uses.
     */
    private static function stateCenters(): array
    {
        return self::$stateCenters ??= collect(
            json_decode(File::get(database_path('data/states-with-coordinates.json')), true)
        )->keyBy('name')->all();
    }
}
