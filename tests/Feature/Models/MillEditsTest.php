<?php

use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\MillType;
use App\Models\State;

beforeEach(function () {
    $this->state = State::factory()->create();
    $this->mill = Mill::factory()->for($this->state)->create();
});

test('each submission gets its own review, approve and reject hashes', function () {
    [$a, $b] = MillEdit::factory()->for($this->mill)->count(2)->create();

    $hashes = [$a->review_hash, $a->approve_hash, $a->reject_hash, $b->review_hash, $b->approve_hash, $b->reject_hash];
    expect(array_unique($hashes))->toHaveCount(6)
        ->and(array_filter($hashes))->toHaveCount(6);
});

test('didAddressChange() spots changes to the physical address or state', function (array $changes, bool $changed) {
    $edit = MillEdit::factory()->for($this->mill)->withChanges($changes)->create();

    expect($edit->didAddressChange())->toBe($changed);
})->with([
    'street' => [['physical_address' => '9 New St'], true],
    'city' => [['physical_city' => 'Macon'], true],
    'zip' => [['physical_zip' => '31201'], true],
    'state' => [['state_id' => 999], true],
    'name only' => [['mill_name' => 'New Name'], false],
    'phone only' => [['telephone' => '706-555-0199'], false],
]);

test('the review page sees mill types by name, and the mill\'s other values where unchanged', function () {
    $sawmill = MillType::factory()->create();
    $edit = MillEdit::factory()->for($this->mill)->withChanges(['mill_types' => [$sawmill->id]])->create();

    $submitted = $edit->prepareSubmitted();

    expect($submitted['mill_types'])->toBe([$sawmill->name])
        ->and($submitted['mill_name'])->toBe($this->mill->mill_name)
        ->and($submitted['state'])->toBe($this->state->name);
});

test('a new business has no original mill to compare against', function () {
    $edit = MillEdit::factory()->newBusiness(['state_id' => $this->state->id])->create();

    expect($edit->isNewMill())->toBeTrue()
        ->and($edit->originalMill())->toBe([]);
});
