<?php

use App\Helpers\Geo;
use App\Models\County;
use App\Models\Mill;
use App\Models\MillType;
use App\Models\State;
use App\Models\WoodSpecies;

/**
 * /api/v1/mills: the JSON behind the mill list and map. Responses aren't wrapped in 'data' (see AppServiceProvider).
 */

beforeEach(function () {
    $this->state = State::factory()->create();
});

function apiMillNames($response): array
{
    return collect($response->json())->pluck('mill_name')->sort()->values()->all();
}

describe('listing', function () {
    test('it lists published mills with their details', function () {
        $sawmill = MillType::factory()->create();
        $mill = Mill::factory()->for($this->state)->hasAttached($sawmill)->create();

        $this->getJson('/api/v1/mills')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.match_id', $mill->match_id)
            ->assertJsonPath('0.state', $this->state->abbreviation)
            ->assertJsonPath('0.state_name', $this->state->name)
            ->assertJsonPath('0.mill_types.0.name', $sawmill->name)
            ->assertJsonPath('0.latitude', (float) $mill->latitude);
    });

    test('unpublished mills are left out', function () {
        Mill::factory()->for($this->state)->create(['mill_name' => 'Published']);
        Mill::factory()->for($this->state)->pending()->create(['mill_name' => 'Pending']);
        Mill::factory()->for($this->state)->rejected()->create(['mill_name' => 'Rejected']);

        expect(apiMillNames($this->getJson('/api/v1/mills')))->toBe(['Published']);
    });

    test('it searches by name', function () {
        Mill::factory()->for($this->state)->create(['mill_name' => 'Timber Creek Lumber']);
        Mill::factory()->for($this->state)->create(['mill_name' => 'Pine Ridge Pallet']);

        expect(apiMillNames($this->getJson('/api/v1/mills?q=creek')))->toBe(['Timber Creek Lumber']);
    });

    test('it filters by mill type and wood species', function () {
        $sawmill = MillType::factory()->create();
        $pine = WoodSpecies::factory()->create();
        Mill::factory()->for($this->state)->hasAttached($sawmill)->create(['mill_name' => 'Sawmill']);
        Mill::factory()->for($this->state)->hasAttached($pine)->create(['mill_name' => 'Pine']);
        Mill::factory()->for($this->state)->create(['mill_name' => 'Neither']);

        expect(apiMillNames($this->getJson("/api/v1/mills?millType={$sawmill->id}")))->toBe(['Sawmill'])
            ->and(apiMillNames($this->getJson("/api/v1/mills?woodSpecies={$pine->id}")))->toBe(['Pine']);
    });

    test('it filters by state and county', function () {
        $other = State::factory()->create();
        $county = County::forceCreate(['name' => 'Clarke', 'type' => 'county', 'state_id' => $this->state->id]);
        Mill::factory()->for($this->state)->create(['mill_name' => 'In County', 'county_id' => $county->id]);
        Mill::factory()->for($this->state)->create(['mill_name' => 'In State']);
        Mill::factory()->for($other)->create(['mill_name' => 'Elsewhere']);

        expect(apiMillNames($this->getJson("/api/v1/mills?state={$this->state->id}")))->toBe(['In County', 'In State'])
            ->and(apiMillNames($this->getJson("/api/v1/mills?county={$county->id}")))->toBe(['In County']);
    });

    test('a radius search finds mills near the point and leaves out far ones', function () {
        // Athens, GA; the other mill is about 60 miles away in Atlanta
        Mill::factory()->for($this->state)->create(['mill_name' => 'Here', 'latitude' => 33.9519, 'longitude' => -83.3576]);
        Mill::factory()->for($this->state)->create(['mill_name' => 'Nearby', 'latitude' => 33.9800, 'longitude' => -83.4000]);
        Mill::factory()->for($this->state)->create(['mill_name' => 'Far', 'latitude' => 33.7490, 'longitude' => -84.3880]);

        $response = $this->getJson('/api/v1/mills?lat=33.9519&lng=-83.3576&radius=10');

        expect(apiMillNames($response))->toBe(['Here', 'Nearby']);
    });

    test('a radius search is a circle, not a square', function () {
        /**
         * 10 miles from Athens, GA due north is inside; the same distance north *and* east (a corner of the bounding
         * square, ~14 miles away) is outside.
         */
        $latitude = 33.9519;
        $longitude = -83.3576;
        $northOffset = Geo::distanceToDegreesLatitude(9.5);
        $eastOffset = Geo::distanceToDegreesLongitude(9.5, $latitude);

        Mill::factory()->for($this->state)->create(['mill_name' => 'North', 'latitude' => $latitude + $northOffset, 'longitude' => $longitude]);
        Mill::factory()->for($this->state)->create(['mill_name' => 'Corner', 'latitude' => $latitude + $northOffset, 'longitude' => $longitude + $eastOffset]);

        expect(apiMillNames($this->getJson("/api/v1/mills?lat={$latitude}&lng={$longitude}&radius=10")))->toBe(['North']);
    });

    test('coordinates without a radius don\'t filter', function () {
        Mill::factory()->for($this->state)->count(2)->create();

        $this->getJson('/api/v1/mills?lat=33.95&lng=-83.36')->assertJsonCount(2);
    });

    test('it can answer in GeoJSON', function () {
        $mill = Mill::factory()->for($this->state)->create(['latitude' => 33.95, 'longitude' => -83.36]);

        $this->getJson('/api/v1/mills?geojson=1')
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonPath('features.0.id', $mill->match_id)
            ->assertJsonPath('features.0.geometry.coordinates', [-83.36, 33.95]);
    });

    test('POST works the same as GET', function () {
        Mill::factory()->for($this->state)->create(['mill_name' => 'Timber Creek Lumber']);
        Mill::factory()->for($this->state)->create(['mill_name' => 'Pine Ridge Pallet']);

        expect(apiMillNames($this->postJson('/api/v1/mills', ['q' => 'creek'])))->toBe(['Timber Creek Lumber']);
    });

    test('invalid filters are rejected', function (string $query, string $field) {
        $this->getJson("/api/v1/mills?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'unknown mill type' => ['millType=999999', 'millType'],
        'unknown state' => ['state=999999', 'state'],
        'latitude out of range' => ['lat=91&lng=0&radius=5', 'lat'],
        'longitude out of range' => ['lat=0&lng=181&radius=5', 'lng'],
    ]);
});

describe('a single mill', function () {
    test('it shows a published mill by match_id', function () {
        $mill = Mill::factory()->for($this->state)->create();

        $this->getJson("/api/v1/mills/{$mill->match_id}")
            ->assertOk()
            ->assertJsonPath('mill_name', $mill->mill_name);
    });

    test('it can answer in GeoJSON', function () {
        $mill = Mill::factory()->for($this->state)->create();

        $this->getJson("/api/v1/mills/{$mill->match_id}?geojson=1")
            ->assertOk()
            ->assertJsonPath('type', 'Feature')
            ->assertJsonPath('properties.name', $mill->mill_name);
    });

    test('unpublished mills 404', function () {
        $mill = Mill::factory()->for($this->state)->pending()->create();

        $this->getJson("/api/v1/mills/{$mill->match_id}")->assertNotFound();
    });
});
