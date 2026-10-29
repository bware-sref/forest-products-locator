<?php

/**
 * Mill::booted()'s saving hook: a changed physical address without new coordinates flags needs_geocode.
 */

test('a new mill with an address and coordinates is trusted', function () {
    $mill = makePendingMill([
        'physical_address' => '123 Timber Rd',
        'physical_city'    => 'Athens',
        'latitude'         => 33.95,
        'longitude'        => -83.38,
    ]);

    expect(freshMill($mill)->needs_geocode)->toBeFalse();
});

test('a new mill with an address but no coordinates is flagged', function () {
    $mill = makePendingMill(['physical_address' => '123 Timber Rd', 'latitude' => null]);

    expect(freshMill($mill)->needs_geocode)->toBeTrue();
});

test('changing the address without new coordinates flags the mill', function (string $field, string $value) {
    $mill = makePendingMill(['physical_address' => '123 Timber Rd', 'latitude' => 33.95, 'longitude' => -83.38]);

    $mill->update([$field => $value]);

    expect(freshMill($mill)->needs_geocode)->toBeTrue();
})->with([
    'street' => ['physical_address', '9 New St'],
    'city' => ['physical_city', 'Macon'],
    'state' => ['physical_state', 'AL'],
    'zip' => ['physical_zip', '31201'],
    'raw address' => ['raw_physical_address', '9 New St Macon GA'],
]);

test('changing the address along with new coordinates is trusted', function () {
    $mill = makePendingMill(['physical_address' => '123 Timber Rd', 'latitude' => 33.95, 'longitude' => -83.38]);

    $mill->update(['physical_address' => '9 New St', 'latitude' => 32.84, 'longitude' => -83.63]);

    expect(freshMill($mill)->needs_geocode)->toBeFalse();
});

test('changing other fields leaves the flag alone', function () {
    $mill = makePendingMill(['physical_address' => '123 Timber Rd', 'latitude' => 33.95, 'longitude' => -83.38]);

    $mill->update(['mill_name' => 'Renamed Mill', 'mailing_city' => 'Macon']);

    expect(freshMill($mill)->needs_geocode)->toBeFalse();
});

test('saving geocode results clears the flag, even though they change the address', function () {
    $mill = makePendingMill(['physical_address' => '123 timber road', 'latitude' => null]);

    $mill->applyGeocodeResults(['physical_address' => '123 Timber Rd', 'latitude' => 33.95, 'longitude' => -83.38]);

    expect(freshMill($mill))
        ->needs_geocode->toBeFalse()
        ->physical_address->toBe('123 Timber Rd');
});

test('a street address is more than a city', function () {
    expect(makePendingMill(['physical_city' => 'Raleigh', 'physical_state' => 'NC'])->hasStreetAddress())->toBeFalse()
        ->and(makePendingMill(['physical_address' => '1 Main St'])->hasStreetAddress())->toBeTrue()
        ->and(makePendingMill(['raw_physical_address' => '1 Main St Raleigh NC'])->hasStreetAddress())->toBeTrue();
});
