<?php

use App\Enums\ImportStatus;
use App\Enums\PublicationStatus;
use App\Jobs\FinalizeMillImport;
use App\Jobs\ProcessImportedMills;
use App\Models\State;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Testing\Fakes\BatchFake;

beforeEach(function () {
    Bus::fake();

    $this->state = State::forceCreate(['name' => 'Alabama', 'abbreviation' => 'AL']);
    $this->import = makeImport($this->state->id, ['delete_from_state' => true]);
});

/**
 * A stand-in for the running batch, to hand to the batch's catch/finally callbacks.
 */
function fakeBatch(): BatchFake
{
    return new BatchFake('batch-id', '', 1, 1, 0, [], [], now()->toImmutable());
}

/**
 * Run the job and return the batch it dispatched.
 */
function dispatchedBatch(ProcessImportedMills $job): PendingBatch
{
    $job->handle();

    $batch = null;
    Bus::assertBatched(function (PendingBatch $pending) use (&$batch) {
        $batch = $pending;

        return true;
    });

    return $batch;
}

test('it batches one processing chain per pending mill in the import', function () {
    makePendingMill(['import_id' => $this->import->id]);
    makePendingMill(['import_id' => $this->import->id]);
    // not included: already published, or from another import
    makePendingMill(['import_id' => $this->import->id, 'status' => PublicationStatus::Approved]);
    makePendingMill(['import_id' => makeImport()->id]);

    $batch = dispatchedBatch(new ProcessImportedMills($this->import));

    expect($batch->jobs)->toHaveCount(2)
        ->and($batch->jobs->first())->toBeArray()->toHaveCount(5);
});

test('an import with no pending mills dispatches nothing', function () {
    (new ProcessImportedMills($this->import))->handle();

    Bus::assertNothingBatched();
});

test('spreadsheet imports stop on the first failure; ArcGIS imports keep going', function (bool $allowFailures) {
    makePendingMill(['import_id' => $this->import->id]);

    $batch = dispatchedBatch(new ProcessImportedMills($this->import, $allowFailures));

    expect($batch->allowsFailures())->toBe($allowFailures)
        ->and($batch->jobs->first()[0]->allowFailures)->toBe($allowFailures);
})->with([
    'spreadsheet' => false,
    'ArcGIS' => true,
]);

test('when the batch fails, the import is marked failed with the error', function () {
    makePendingMill(['import_id' => $this->import->id]);

    $batch = dispatchedBatch(new ProcessImportedMills($this->import));
    $batch->catchCallbacks()[0](fakeBatch(), new RuntimeException('Row 7 is broken'));

    expect($this->import->fresh())
        ->status->toBe(ImportStatus::Failed)
        ->errors->toBe('Row 7 is broken');
});

test('when the batch finishes, it dispatches FinalizeMillImport for this import', function () {
    makePendingMill(['import_id' => $this->import->id]);

    $batch = dispatchedBatch(new ProcessImportedMills($this->import));
    $batch->finallyCallbacks()[0](fakeBatch());

    Bus::assertDispatched(FinalizeMillImport::class, fn (FinalizeMillImport $job) =>
        $job->importId === $this->import->id
        && $job->stateId === $this->state->id
        && $job->deleteFromState === true
    );
});
