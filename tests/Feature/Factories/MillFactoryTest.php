<?php

use App\Enums\PublicationStatus;
use App\Models\County;
use App\Models\Mill;
use App\Models\State;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->georgia = State::forceCreate(['name' => 'Georgia', 'abbreviation' => 'GA']);
});

function georgiaCounties(): array
{
    return collect(json_decode(File::get(database_path('data/us-counties-by-state__only-south.json')), true))
        ->where('state_name', 'Georgia')
        ->keyBy('name')
        ->all();
}

test('a mill lands within a few miles of a real county in its state', function () {
    $mill = Mill::factory()->for($this->georgia)->create();
    $county = georgiaCounties()[$mill->county_name] ?? null;

    expect($county)->not->toBeNull()
        ->and($mill->physical_state)->toBe('GA')
        ->and(abs($mill->latitude - $county['latitude']))->toBeLessThan(0.1)
        ->and(abs($mill->longitude - $county['longitude']))->toBeLessThan(0.1);
});

test('a default mill is approved, complete, and trusted as-is', function () {
    $mill = Mill::factory()->for($this->georgia)->create();

    expect(Mill::find($mill->id))->not->toBeNull() // visible through ApprovedScope
        ->and($mill->match_id)->not->toBeEmpty()
        ->and($mill->hasStreetAddress())->toBeTrue()
        ->and($mill->hasLatLng())->toBeTrue()
        ->and(freshMill($mill)->needs_geocode)->toBeFalse()
        ->and($mill->mailing_state_id)->toBe($this->georgia->id);
});

test('county_id is filled in when the county exists', function () {
    $county = County::forceCreate(['name' => 'Clarke', 'type' => 'county', 'state_id' => $this->georgia->id]);

    $mill = Mill::factory()->for($this->georgia)->create(['county_name' => 'Clarke']);

    expect($mill->county_id)->toBe($county->id);
});

test('a state without county data falls back to the state\'s center', function () {
    $ohio = State::forceCreate(['name' => 'Ohio', 'abbreviation' => 'OH']);

    $mill = Mill::factory()->for($ohio)->create();

    expect($mill->county_name)->toBeNull()
        ->and($mill->hasLatLng())->toBeTrue()
        ->and(abs($mill->latitude - 40.4173))->toBeLessThan(0.1);
});

test('fromImport makes a pending mill in the import\'s state', function () {
    $import = makeImport($this->georgia->id);

    $mill = Mill::factory()->fromImport($import)->create();

    expect($mill)
        ->status->toBe(PublicationStatus::Pending)
        ->import_id->toBe($import->id)
        ->state_id->toBe($this->georgia->id);
});

test('the incomplete-location states produce what GeocodeMill expects', function () {
    $noCoordinates = Mill::factory()->for($this->georgia)->withoutCoordinates()->create();
    $noAddress = Mill::factory()->for($this->georgia)->withoutAddress()->create();
    $nothing = Mill::factory()->for($this->georgia)->withoutLocation()->create();

    expect($noCoordinates->hasLatLng())->toBeFalse()
        ->and($noCoordinates->hasAddress())->toBeTrue()
        ->and($noAddress->hasStreetAddress())->toBeFalse()
        ->and($noAddress->hasLatLng())->toBeTrue()
        ->and($nothing->hasAddress())->toBeFalse()
        ->and($nothing->hasLatLng())->toBeFalse();
});
