<?php

use App\Models\Import;
use RedSquirrelStudio\LaravelBackpackImportOperation\Columns;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;

/**
 * Configurations for ImportOperation.
 */

return [
    // 'import_log_model' => ImportLog::class,
    'import_log_model' => Import::class,

    //Filesystem disk to store uploaded import files
    'disk' => env('FILESYSTEM_DISK', 'local'),

    //Path to store uploaded import files
    'path' => env('BACKPACK_IMPORT_FILE_PATH', 'imports'),

    //Queue to dispatch import jobs to
    // sync is a queue connection rather than a queue
    // in any case, when using the DB for queues, the value of 'QUEUE_CONNECTION' is 'database', which is not the default queue
    // 'queue' => env('QUEUE_CONNECTION', 'sync'),
    'queue' => env('IMPORT_QUEUE', 'default'),

    //Chunk size for reading import files
    'chunk_size' => env('BACKPACK_IMPORT_CHUNK_SIZE', 100),

    // Aliases for import column types to be used in operation setup
    'column_aliases' => [
        'array' => Columns\ArrayColumn::class,
        'boolean' => Columns\BooleanColumn::class,
        'date' => Columns\DateColumn::class,
        'number' => Columns\NumberColumn::class,
        'text' => Columns\TextColumn::class,
    ]
];
