<?php

use App\Enums\PublicationStatus;
use App\Jobs\GeocodeMill;
use App\Services\CensusGeocoderService;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Bus;

/**
 * A mill GeocodeMill would normally skip: street address and coordinates, nothing changed since.
 */
function completeMill(array $attributes = []): \App\Models\Mill
{
    return makePendingMill([
        'physical_address' => '123 Timber Rd',
        'physical_city'    => 'Athens',
        'latitude'         => 33.95,
        'longitude'        => -83.38,
        ...$attributes,
    ]);
}

test('it dispatches a job per mill without touching the flag', function () {
    Bus::fake([GeocodeMill::class]);
    [$a, $b] = [completeMill(), completeMill()];

    $this->artisan('zed:geocode-mill', ['mill' => [$a->id, $b->id]])->assertSuccessful();

    Bus::assertDispatchedTimes(GeocodeMill::class, 2);
    expect(freshMill($a)->needs_geocode)->toBeFalse();
});

test('--force flags the mill before dispatching', function (PublicationStatus $status) {
    Bus::fake([GeocodeMill::class]);
    $mill = completeMill(['status' => $status]);

    $this->artisan('zed:geocode-mill', ['mill' => [$mill->id], '--force' => true])
        ->expectsOutputToContain('(forced)')
        ->assertSuccessful();

    expect(freshMill($mill)->needs_geocode)->toBeTrue();
    Bus::assertDispatched(GeocodeMill::class, fn (GeocodeMill $job) => $job->mill->is($mill));
})->with([
    'pending' => PublicationStatus::Pending,
    'approved' => PublicationStatus::Approved,
    'rejected' => PublicationStatus::Rejected,
]);

test('--force makes the job look up a mill it would otherwise skip', function () {
    $geo = $this->mock(GeocodingService::class);
    $this->mock(CensusGeocoderService::class);
    $geo->shouldReceive('geocode')->once()->andReturn([[
        'street_address' => '125 Timber Rd',
        'city' => 'Athens',
        'county' => 'Clarke',
        'zip' => '30601',
        'latitude' => 33.96,
        'longitude' => -83.39,
    ]]);
    $mill = completeMill();

    $this->artisan('zed:geocode-mill', ['mill' => [$mill->id], '--force' => true])->assertSuccessful();

    expect(freshMill($mill))
        ->physical_address->toBe('125 Timber Rd')
        ->needs_geocode->toBeFalse();
});

test('missing mills are reported and fail the command', function () {
    Bus::fake([GeocodeMill::class]);
    $mill = completeMill();

    $this->artisan('zed:geocode-mill', ['mill' => [$mill->id, 999999]])
        ->expectsOutputToContain("No Mill found with id '999999'")
        ->assertFailed();

    Bus::assertDispatchedTimes(GeocodeMill::class, 1);
});
