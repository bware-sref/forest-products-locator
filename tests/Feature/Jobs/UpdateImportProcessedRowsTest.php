<?php

use App\Jobs\UpdateImportProcessedRows;

test('it counts the mill as a processed row on its import', function () {
    $import = makeImport();
    $mill = makePendingMill(['import_id' => $import->id]);

    (new UpdateImportProcessedRows($mill))->handle();
    (new UpdateImportProcessedRows(makePendingMill(['import_id' => $import->id])))->handle();

    expect($import->fresh()->processed_rows)->toBe(2);
});

test('a mill that isn\'t from an import is left alone', function () {
    $mill = makePendingMill();

    (new UpdateImportProcessedRows($mill))->handle();

    expect(freshMill($mill)->import_id)->toBeNull();
});
