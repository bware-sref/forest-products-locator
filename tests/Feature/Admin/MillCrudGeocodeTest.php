<?php

use App\Enums\PublicationStatus;
use App\Jobs\GeocodeMill;
use App\Models\Mill;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Permission;

/**
 * Mills saved in the admin panel get geocoded when they lack coordinates or their address changed.
 * GeocodeMill's own decisions are covered in tests/Feature/Jobs/GeocodeMillTest.php.
 */

beforeEach(function () {
    Bus::fake([GeocodeMill::class]);

    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('mills.edit', 'web'));
    backpack_auth()->setUser($user);
});

function approvedMill(array $attributes = []): Mill
{
    return makePendingMill(['status' => PublicationStatus::Approved, ...$attributes]);
}

test('a new mill without coordinates is geocoded', function () {
    $this->post('/admin/mill', [
        'mill_name' => 'New Admin Mill',
        'physical_address' => '123 Timber Rd',
        'physical_city' => 'Athens',
    ])->assertRedirect();

    Bus::assertDispatched(GeocodeMill::class, fn (GeocodeMill $job) =>
        $job->mill->mill_name === 'New Admin Mill' && $job->allowFailures
    );
});

test('a new mill with an address and coordinates is not geocoded', function () {
    $this->post('/admin/mill', [
        'mill_name' => 'New Admin Mill',
        'physical_address' => '123 Timber Rd',
        'latitude' => 33.95,
        'longitude' => -83.38,
    ])->assertRedirect();

    Bus::assertNotDispatched(GeocodeMill::class);
});

test('changing a mill\'s address geocodes it', function () {
    $mill = approvedMill(['physical_address' => '1 Old Rd', 'latitude' => 33.95, 'longitude' => -83.38]);

    $this->put("/admin/mill/{$mill->id}", [
        'id' => $mill->id,
        'mill_name' => $mill->mill_name,
        'physical_address' => '9 New St',
        'latitude' => 33.95,
        'longitude' => -83.38,
    ])->assertRedirect();

    Bus::assertDispatched(GeocodeMill::class, fn (GeocodeMill $job) => $job->mill->is($mill));
});

test('an edit that doesn\'t touch the address is not geocoded', function () {
    $mill = approvedMill(['physical_address' => '1 Old Rd', 'latitude' => 33.95, 'longitude' => -83.38]);

    $this->put("/admin/mill/{$mill->id}", [
        'id' => $mill->id,
        'mill_name' => 'Renamed Mill',
        'physical_address' => '1 Old Rd',
        'latitude' => 33.95,
        'longitude' => -83.38,
    ])->assertRedirect();

    Bus::assertNotDispatched(GeocodeMill::class);
});

test('a mill with neither address nor coordinates is not sent to be marked invalid', function () {
    $this->post('/admin/mill', ['mill_name' => 'Nowhere Mill'])->assertRedirect();

    Bus::assertNotDispatched(GeocodeMill::class);
});
