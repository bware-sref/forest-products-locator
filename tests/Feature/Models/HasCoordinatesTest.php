<?php

use App\Models\County;
use App\Models\Mill;
use App\Models\State;

/**
 * latitude/longitude are DOUBLE columns on mills, states and counties (HasCoordinates).
 */

test('coordinates come back as floats, whether given as numbers or numeric strings', function (mixed $latitude, mixed $longitude) {
    $mill = makePendingMill(['latitude' => $latitude, 'longitude' => $longitude]);

    expect(freshMill($mill))
        ->latitude->toBe(33.95)
        ->longitude->toBe(-83.38);
})->with([
    'numbers' => [33.95, -83.38],
    'strings' => ['33.95', '-83.38'],
    'padded strings' => [' 33.95 ', ' -83.38 '],
]);

test('blank or non-numeric coordinates are stored as null instead of failing the save', function (mixed $value) {
    $mill = makePendingMill(['latitude' => $value, 'longitude' => $value]);

    expect(freshMill($mill))
        ->latitude->toBeNull()
        ->longitude->toBeNull();
})->with([
    'empty string' => '',
    'spaces' => '   ',
    'text' => 'N/A',
    'null' => null,
]);

test('every stored decimal place survives the round trip', function () {
    $mill = makePendingMill(['latitude' => 38.84505589808, 'longitude' => -76.92836638093]);

    expect(freshMill($mill))
        ->latitude->toBe(38.84505589808)
        ->longitude->toBe(-76.92836638093);
});

test('setting the same coordinate as a string is not a change', function () {
    $mill = freshMill(makePendingMill(['latitude' => 33.95, 'longitude' => -83.38]));

    $mill->latitude = '33.95';
    $mill->longitude = '-83.380';

    expect($mill->isDirty(['latitude', 'longitude']))->toBeFalse();
});

test('states and counties store coordinates as floats too', function () {
    $state = State::factory()->create(['latitude' => '32.5', 'longitude' => '']);
    $county = County::factory()->create(['state_id' => $state->id, 'name' => 'Test', 'latitude' => '31.25', 'longitude' => '-84.5']);

    expect($state->fresh())
        ->latitude->toBe(32.5)
        ->longitude->toBeNull()
        ->and($county->fresh())
        ->latitude->toBe(31.25)
        ->longitude->toBe(-84.5);
});
