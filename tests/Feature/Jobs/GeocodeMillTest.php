<?php

use App\Enums\PublicationStatus;
use App\Jobs\GeocodeMill;
use App\Services\CensusGeocoderService;
use App\Services\GeocodingService;

/**
 * GeocodingService (AWS Location) and the Census geocoder are stubbed: these tests cover what GeocodeMill does
 * with their results, not the lookups themselves (see CensusGeocoderServiceTest for that side).
 */

beforeEach(function () {
    $this->geo = Mockery::mock(GeocodingService::class);
    $this->census = Mockery::mock(CensusGeocoderService::class);
    $this->census->shouldNotReceive('oneLineAddress');
});

/**
 * One result in the shape GeocodingService::geocode()/reverse() return.
 */
function geocodeResult(array $overrides = []): array
{
    return [[
        'street_address' => '123 Timber Rd',
        'city'           => 'Athens',
        'county'         => 'Clarke',
        'zip'            => '30601',
        'latitude'       => 33.95,
        'longitude'      => -83.38,
        ...$overrides,
    ]];
}

test('a mill with an address gets its coordinates and normalized address', function () {
    $mill = makePendingMill([
        'raw_physical_address' => '123 timber road athens ga',
        'physical_state'       => 'GA',
    ]);

    $this->geo->shouldReceive('geocode')
        ->once()
        ->withArgs(fn (string $query) => $query === '123 timber road athens ga')
        ->andReturn(geocodeResult());
    $this->geo->shouldNotReceive('reverse');

    (new GeocodeMill($mill))->handle($this->geo, $this->census);

    $mill = freshMill($mill);
    expect($mill)
        ->physical_address->toBe('123 Timber Rd')
        ->physical_city->toBe('Athens')
        ->county_name->toBe('Clarke')
        ->physical_zip->toBe('30601')
        ->and((float) $mill->latitude)->toBe(33.95)
        ->and((float) $mill->longitude)->toBe(-83.38);
});

test('a mill with only coordinates is reverse geocoded', function () {
    $mill = makePendingMill(['latitude' => 33.95, 'longitude' => -83.38]);

    $this->geo->shouldNotReceive('geocode');
    $this->geo->shouldReceive('reverse')
        ->once()
        ->withArgs(fn ($longitude, $latitude) => (float) $longitude === -83.38 && (float) $latitude === 33.95)
        ->andReturn(geocodeResult());

    (new GeocodeMill($mill))->handle($this->geo, $this->census);

    expect(freshMill($mill))
        ->physical_address->toBe('123 Timber Rd')
        ->physical_city->toBe('Athens');
});

test('a state on its own doesn\'t count as an address', function () {
    /**
     * e.g., North Carolina's ArcGIS data: no address, but the state is known, so the raw address is just "NC".
     */
    $mill = makePendingMill(['physical_state' => 'NC', 'latitude' => 35.7, 'longitude' => -79.0]);

    $this->geo->shouldNotReceive('geocode');
    $this->geo->shouldReceive('reverse')->once()->andReturn(geocodeResult());

    (new GeocodeMill($mill))->handle($this->geo, $this->census);
});

test('a street address with coordinates is trusted and not looked up', function () {
    /**
     * e.g., state data (ArcGIS or spreadsheet) that came with both.
     */
    $mill = makePendingMill([
        'physical_address' => '123 Timber Rd',
        'physical_city'    => 'Athens',
        'latitude'         => 33.95,
        'longitude'        => -83.38,
    ]);

    $this->geo->shouldNotReceive('geocode');
    $this->geo->shouldNotReceive('reverse');

    (new GeocodeMill($mill))->handle($this->geo, $this->census);
});

test('coordinates with only a city are reverse geocoded to find the street', function () {
    $mill = makePendingMill(['physical_city' => 'Athens', 'physical_state' => 'GA', 'latitude' => 33.95, 'longitude' => -83.38]);

    $this->geo->shouldNotReceive('geocode');
    $this->geo->shouldReceive('reverse')->once()->andReturn(geocodeResult());

    (new GeocodeMill($mill))->handle($this->geo, $this->census);

    expect(freshMill($mill)->physical_address)->toBe('123 Timber Rd');
});

test('a changed address is geocoded even though the mill has coordinates', function () {
    $mill = makePendingMill(['physical_address' => '1 Old Rd', 'latitude' => 10, 'longitude' => 10]);
    $mill->update(['physical_address' => '123 timber road athens ga']);
    expect($mill->needs_geocode)->toBeTrue();

    $this->geo->shouldNotReceive('reverse');
    $this->geo->shouldReceive('geocode')->once()->andReturn(geocodeResult());

    (new GeocodeMill($mill))->handle($this->geo, $this->census);

    $mill = freshMill($mill);
    expect($mill->needs_geocode)->toBeFalse()
        ->and((float) $mill->latitude)->toBe(33.95)
        ->and($mill->physical_address)->toBe('123 Timber Rd');
});

test('a lookup that confirms what the mill already has still clears the flag', function () {
    $result = geocodeResult()[0];
    $mill = makePendingMill([...array_intersect_key($result, array_flip(['city', 'zip'])), 'latitude' => 1, 'longitude' => 1]);
    $mill->update([
        'physical_address' => $result['street_address'],
        'physical_city'    => $result['city'],
        'county_name'      => $result['county'],
        'physical_zip'     => $result['zip'],
    ]);
    // coordinates already right, but set in a separate save, so the address change above flagged the mill
    $mill->updateQuietly(['latitude' => $result['latitude'], 'longitude' => $result['longitude']]);
    $mill = freshMill($mill);

    $this->geo->shouldReceive('geocode')->once()->andReturn([$result]);

    (new GeocodeMill($mill))->handle($this->geo, $this->census);

    expect(freshMill($mill)->needs_geocode)->toBeFalse();
});

test('no lookup results leaves the mill as it was', function () {
    $mill = makePendingMill(['raw_physical_address' => '1 Nowhere Ln', 'physical_city' => 'Old City']);

    $this->geo->shouldReceive('geocode')->once()->andReturn(null);

    (new GeocodeMill($mill))->handle($this->geo, $this->census);

    expect(freshMill($mill))
        ->physical_city->toBe('Old City')
        ->latitude->toBeNull()
        // still flagged, so a later run tries again
        ->needs_geocode->toBeTrue();
});

test('a mill with neither address nor coordinates is marked invalid and fails the job', function () {
    $import = makeImport();
    $mill = makePendingMill(['import_id' => $import->id]);

    $this->geo->shouldNotReceive('geocode');
    $this->geo->shouldNotReceive('reverse');

    expect(fn () => (new GeocodeMill($mill))->handle($this->geo, $this->census))
        ->toThrow(RuntimeException::class, 'neither an address nor a latitude & longitude');

    expect(freshMill($mill)->status)->toBe(PublicationStatus::Invalid)
        ->and($import->fresh()->failed_rows)->toBe(1);
});

test('with allowFailures, a mill with nothing to geocode is recorded but doesn\'t throw', function () {
    $import = makeImport();
    $mill = makePendingMill(['import_id' => $import->id]);

    (new GeocodeMill($mill, allowFailures: true))->handle($this->geo, $this->census);

    expect(freshMill($mill)->status)->toBe(PublicationStatus::Invalid)
        ->and($import->fresh()->failed_rows)->toBe(1);
});

test('a lookup error fails the job and counts a failed row', function () {
    $import = makeImport();
    $mill = makePendingMill(['import_id' => $import->id, 'raw_physical_address' => '123 Timber Rd Athens GA']);

    $this->geo->shouldReceive('geocode')->once()->andThrow(new RuntimeException('AWS is down'));

    expect(fn () => (new GeocodeMill($mill))->handle($this->geo, $this->census))
        ->toThrow(RuntimeException::class, 'AWS is down');

    expect($import->fresh()->failed_rows)->toBe(1);
});
