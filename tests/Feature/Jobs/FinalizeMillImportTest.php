<?php

use App\Enums\ImportStatus;
use App\Enums\PublicationStatus;
use App\Jobs\FinalizeMillImport;
use App\Models\State;

beforeEach(function () {
    $this->alabama = State::forceCreate(['name' => 'Alabama', 'abbreviation' => 'AL']);
    $this->georgia = State::forceCreate(['name' => 'Georgia', 'abbreviation' => 'GA']);

    $this->oldImport = makeImport($this->alabama->id, ['status' => ImportStatus::Completed]);
    $this->newImport = makeImport($this->alabama->id);

    $approved = ['status' => PublicationStatus::Approved];

    $this->fromOldImport = makePendingMill(['state_id' => $this->alabama->id, 'import_id' => $this->oldImport->id, ...$approved]);
    $this->fromNewImport = makePendingMill(['state_id' => $this->alabama->id, 'import_id' => $this->newImport->id]);
    $this->userSubmitted = makePendingMill(['state_id' => $this->alabama->id, ...$approved]);
    $this->otherState = makePendingMill(['state_id' => $this->georgia->id, 'import_id' => $this->oldImport->id, ...$approved]);
});

test('it publishes the import\'s mills and marks the import completed', function () {
    (new FinalizeMillImport($this->newImport->id, $this->alabama->id))->handle();

    expect(freshMill($this->fromNewImport)->status)->toBe(PublicationStatus::Approved)
        ->and($this->newImport->fresh()->status)->toBe(ImportStatus::Completed);
});

test('with deleteFromState, it replaces the state\'s previously imported mills', function () {
    (new FinalizeMillImport($this->newImport->id, $this->alabama->id, deleteFromState: true))->handle();

    expect(freshMill($this->fromOldImport))->toBeNull()
        ->and(freshMill($this->fromNewImport))->not->toBeNull();
});

test('deleteFromState keeps user-submitted mills and other states\' mills', function () {
    (new FinalizeMillImport($this->newImport->id, $this->alabama->id, deleteFromState: true))->handle();

    expect(freshMill($this->userSubmitted))->not->toBeNull()
        ->and(freshMill($this->otherState))->not->toBeNull();
});

test('without deleteFromState, older imported mills are kept', function () {
    (new FinalizeMillImport($this->newImport->id, $this->alabama->id, deleteFromState: false))->handle();

    expect(freshMill($this->fromOldImport))->not->toBeNull();
});

test('with no state, nothing is deleted even if deleteFromState is set', function () {
    (new FinalizeMillImport($this->newImport->id, stateId: null, deleteFromState: true))->handle();

    expect(freshMill($this->fromOldImport))->not->toBeNull()
        ->and(freshMill($this->fromNewImport)->status)->toBe(PublicationStatus::Approved);
});

test('only pending mills from the import are published', function () {
    $invalid = makePendingMill(['import_id' => $this->newImport->id, 'status' => PublicationStatus::Invalid]);

    (new FinalizeMillImport($this->newImport->id, $this->alabama->id))->handle();

    expect(freshMill($invalid)->status)->toBe(PublicationStatus::Invalid);
});
