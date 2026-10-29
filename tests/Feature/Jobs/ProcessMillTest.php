<?php

use App\Jobs\GeocodeMill;
use App\Jobs\ProcessMill;
use App\Jobs\ProcessMillMillTypes;
use App\Jobs\ProcessMillState;
use App\Jobs\ProcessMillWoodSpecies;
use App\Jobs\UpdateImportProcessedRows;
use Illuminate\Support\Facades\Bus;

const MILL_CHAIN = [
    GeocodeMill::class,
    ProcessMillState::class,
    ProcessMillMillTypes::class,
    ProcessMillWoodSpecies::class,
    UpdateImportProcessedRows::class,
];

test('the chain runs geocoding, state, types, species, then the row count', function () {
    $chain = ProcessMill::jobChain(makePendingMill());

    expect(array_map(get_class(...), $chain))->toBe(MILL_CHAIN);
});

test('every job in the chain gets the same mill and allowFailures setting', function (bool $allowFailures) {
    $mill = makePendingMill();

    foreach (ProcessMill::jobChain($mill, $allowFailures) as $job) {
        expect($job->mill->is($mill))->toBeTrue()
            ->and($job->allowFailures)->toBe($allowFailures);
    }
})->with([
    'strict' => false,
    'tolerant' => true,
]);

test('handling it dispatches the chain', function () {
    Bus::fake();

    (new ProcessMill(makePendingMill()))->handle();

    Bus::assertChained(MILL_CHAIN);
});
