<?php

use App\Models\Mill;
use App\Models\MillEdit;

test('an edit\'s diff is built from the mill it belongs to, without making extra mills', function (bool $givenMill) {
    $mill = $givenMill ? Mill::factory()->create(['mill_name' => 'Old Name']) : null;

    $factory = MillEdit::factory()->withChanges(['mill_name' => 'New Name']);
    $edit = ($givenMill ? $factory->for($mill) : $factory)->create();

    expect(Mill::withoutGlobalScopes()->count())->toBe(1)
        ->and($edit->getChanges()['mill_name'])->toBe('New Name')
        ->and($edit->getDiff()['mill_name']['from'])->toBe($edit->mill->mill_name);
})->with(['given a mill' => true, 'making its own mill' => false]);

test('a new business submission has no mill', function () {
    $edit = MillEdit::factory()->newBusiness(['mill_name' => 'Brand New'])->create();

    expect($edit->isNewMill())->toBeTrue()
        ->and($edit->getChanges()['mill_name'])->toBe('Brand New')
        ->and(Mill::withoutGlobalScopes()->count())->toBe(0);
});
