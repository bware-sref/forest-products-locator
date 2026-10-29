<?php

use App\Jobs\ProcessMillMillTypes;
use App\Models\MillType;

test('it attaches existing mill types case-insensitively', function () {
    $sawmill = MillType::create(['name' => 'Sawmill']);
    $mill = makePendingMill(['type' => 'SAWMILL']);

    (new ProcessMillMillTypes($mill))->handle();

    expect($mill->millTypes()->pluck('mill_types.id')->all())->toBe([$sawmill->id])
        ->and(MillType::count())->toBe(1);
});

test('it creates unknown mill types in title case', function () {
    $mill = makePendingMill(['type' => 'pellet mill']);

    (new ProcessMillMillTypes($mill))->handle();

    expect($mill->millTypes()->pluck('name')->all())->toBe(['Pellet Mill']);
});

test('it splits the type field on pipes or commas', function (string $type) {
    MillType::create(['name' => 'Sawmill']);
    $mill = makePendingMill(['type' => $type]);

    (new ProcessMillMillTypes($mill))->handle();

    expect($mill->millTypes()->pluck('name')->sort()->values()->all())->toBe(['Pellet Mill', 'Sawmill']);
})->with([
    'pipes' => 'Sawmill | Pellet Mill',
    'commas' => 'Sawmill, Pellet Mill',
]);

test('running it again doesn\'t duplicate attachments', function () {
    $mill = makePendingMill(['type' => 'Sawmill|Pellet Mill']);

    (new ProcessMillMillTypes($mill))->handle();
    (new ProcessMillMillTypes($mill))->handle();

    expect($mill->millTypes()->count())->toBe(2)
        ->and(MillType::count())->toBe(2);
});

test('an empty type field does nothing', function () {
    $mill = makePendingMill(['type' => null]);

    (new ProcessMillMillTypes($mill))->handle();

    expect($mill->millTypes()->count())->toBe(0)
        ->and(MillType::count())->toBe(0);
});
