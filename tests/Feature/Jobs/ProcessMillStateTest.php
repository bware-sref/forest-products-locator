<?php

use App\Jobs\ProcessMillState;
use App\Models\County;
use App\Models\State;

beforeEach(function () {
    $this->alabama = State::forceCreate(['name' => 'Alabama', 'abbreviation' => 'AL']);
    $this->georgia = State::forceCreate(['name' => 'Georgia', 'abbreviation' => 'GA']);

    $this->autauga = County::forceCreate(['name' => 'Autauga', 'type' => 'county', 'state_id' => $this->alabama->id]);
    County::forceCreate(['name' => 'Baldwin', 'type' => 'county', 'state_id' => $this->alabama->id]);
});

test('it looks up state_id and county_id from the physical address', function () {
    $mill = makePendingMill(['physical_state' => 'AL', 'county_name' => 'Autauga']);

    (new ProcessMillState($mill))->handle();

    expect(freshMill($mill))
        ->state_id->toBe($this->alabama->id)
        ->county_id->toBe($this->autauga->id);
});

test('it accepts a full state name', function () {
    $mill = makePendingMill(['physical_state' => 'Alabama']);

    (new ProcessMillState($mill))->handle();

    expect(freshMill($mill)->state_id)->toBe($this->alabama->id);
});

test('an unknown county is skipped but the state is still saved', function () {
    $mill = makePendingMill(['physical_state' => 'AL', 'county_name' => 'Nowhere']);

    (new ProcessMillState($mill))->handle();

    expect(freshMill($mill))
        ->state_id->toBe($this->alabama->id)
        ->county_id->toBeNull();
});

test('a different mailing state gets its own lookup', function () {
    $mill = makePendingMill(['physical_state' => 'AL', 'mailing_state' => 'GA']);

    (new ProcessMillState($mill))->handle();

    expect(freshMill($mill))
        ->state_id->toBe($this->alabama->id)
        ->mailing_state_id->toBe($this->georgia->id);
});

test('a matching mailing state reuses the physical state', function () {
    $mill = makePendingMill(['physical_state' => 'AL', 'mailing_state' => 'AL']);

    (new ProcessMillState($mill))->handle();

    expect(freshMill($mill)->mailing_state_id)->toBe($this->alabama->id);
});

test('an unknown mailing state leaves mailing_state_id empty', function () {
    $mill = makePendingMill(['physical_state' => 'AL', 'mailing_state' => 'ZZ']);

    (new ProcessMillState($mill))->handle();

    expect(freshMill($mill))
        ->state_id->toBe($this->alabama->id)
        ->mailing_state_id->toBeNull();
});

test('an unknown physical state fails the job and counts a failed row', function () {
    $import = makeImport();
    $mill = makePendingMill(['physical_state' => 'ZZ', 'import_id' => $import->id]);

    expect(fn () => (new ProcessMillState($mill))->handle())
        ->toThrow(RuntimeException::class, "no State found for 'ZZ'");

    expect($import->fresh()->failed_rows)->toBe(1)
        ->and(freshMill($mill)->state_id)->toBeNull();
});

test('with allowFailures, an unknown physical state is recorded but doesn\'t throw', function () {
    $import = makeImport();
    $mill = makePendingMill(['physical_state' => 'ZZ', 'import_id' => $import->id]);

    (new ProcessMillState($mill, allowFailures: true))->handle();

    expect($import->fresh()->failed_rows)->toBe(1);
});
