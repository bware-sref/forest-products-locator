<?php

use App\Jobs\ProcessMillWoodSpecies;
use App\Models\WoodSpecies;

test('it attaches existing wood species case-insensitively', function () {
    $pine = WoodSpecies::create(['name' => 'Southern Yellow Pine']);
    $mill = makePendingMill(['species' => 'southern yellow PINE']);

    (new ProcessMillWoodSpecies($mill))->handle();

    expect($mill->woodSpecies()->pluck('wood_species.id')->all())->toBe([$pine->id])
        ->and(WoodSpecies::count())->toBe(1);
});

test('it creates unknown wood species in title case', function () {
    $mill = makePendingMill(['species' => 'white oak']);

    (new ProcessMillWoodSpecies($mill))->handle();

    expect($mill->woodSpecies()->pluck('name')->all())->toBe(['White Oak']);
});

test('it splits the species field on pipes or commas', function (string $species) {
    $mill = makePendingMill(['species' => $species]);

    (new ProcessMillWoodSpecies($mill))->handle();

    expect($mill->woodSpecies()->pluck('name')->sort()->values()->all())->toBe(['Hardwood', 'Pine']);
})->with([
    'pipes' => 'Pine | Hardwood',
    'commas' => 'Pine, Hardwood',
]);

test('running it again doesn\'t duplicate attachments', function () {
    $mill = makePendingMill(['species' => 'Pine, Hardwood']);

    (new ProcessMillWoodSpecies($mill))->handle();
    (new ProcessMillWoodSpecies($mill))->handle();

    expect($mill->woodSpecies()->count())->toBe(2)
        ->and(WoodSpecies::count())->toBe(2);
});

test('an empty species field does nothing', function () {
    $mill = makePendingMill(['species' => '']);

    (new ProcessMillWoodSpecies($mill))->handle();

    expect($mill->woodSpecies()->count())->toBe(0)
        ->and(WoodSpecies::count())->toBe(0);
});
