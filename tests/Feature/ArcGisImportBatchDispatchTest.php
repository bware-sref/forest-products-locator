<?php

use App\Enums\ImportStatus;
use App\Jobs\ProcessArcGisImport;
use App\Jobs\QueueMillProcessingJobs;
use App\Mappers\GeorgiaMillMapper;
use App\Models\Import;
use App\Models\Mill;
use App\Models\MillRawImport;
use App\Models\State;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Regression coverage for the stack overflow in ProcessArcGisImport::handle():
 * the closures passed to Bus::batch()->catch()/->then() were plain (non-static)
 * closures defined inside an instance method, so PHP implicitly bound $this —
 * the whole job, including the Import model — into them. Serializing that for
 * batch storage blew the stack. QueueMillProcessingJobs had the same pattern.
 *
 * Bus::fake() can't be used here: PendingBatchFake intercepts every
 * Bus::batch() call unconditionally, regardless of job filters, which would
 * skip the exact DatabaseBatchRepository::store()/serialize() path that
 * crashed. Instead these tests force the real 'database' queue connection so
 * the batch is genuinely persisted, while leaving QUEUE_CONNECTION=sync's
 * inline execution out of the picture (batched jobs land in `jobs` unrun,
 * so the downstream mill-processing chain — geocoding etc. — never fires).
 */
beforeEach(function () {
    Config::set('arcgis', [
        'default_disk'      => 'fake',
        'timeout'           => 30,
        'export_path'       => 'arcgis',
        'file_name_pattern' => ':export_path:'.DIRECTORY_SEPARATOR.':timestamp:__:slug:.:extension:',
        'timestamp_format'  => 'Y-m-d\TH-i-s',

        'endpoints' => [
            'ga' => [
                'url'         => 'https://services2.arcgis.com/fake/FeatureServer/0',
                'description' => 'Georgia Wood Mills',
                'disk'        => 'fake',
                'slug'        => 'georgia',
                'mapper'      => GeorgiaMillMapper::class,
            ],
        ],
    ]);

    Storage::fake('fake');

    Config::set('queue.default', 'database');
});

function makeArcGisImport(array $features): Import
{
    $state = State::create(['name' => 'Georgia', 'abbreviation' => 'GA']);

    Storage::disk('fake')->put('arcgis/test-import.geojson', json_encode([
        'type'     => 'FeatureCollection',
        'features' => $features,
    ]));

    return Import::create([
        'state_id'   => $state->id,
        'file_path'  => 'arcgis/test-import.geojson',
        'disk'       => 'fake',
        'model'      => Mill::class,
        'status'     => ImportStatus::Pending,
        'total_rows' => count($features),
    ]);
}

it('dispatches a real batch of CreateMillFromArcGisFeature jobs without crashing on closure serialization', function () {
    $import = makeArcGisImport([
        georgiaFeature(1, 'Mill A'),
        georgiaFeature(2, 'Mill B'),
        georgiaFeature(3, 'Mill C'),
    ]);

    (new ProcessArcGisImport($import, 'ga'))->handle();

    expect(DB::table('job_batches')->count())->toBe(1);

    $batch = DB::table('job_batches')->first();
    expect($batch->total_jobs)->toBe(3)
        ->and($batch->pending_jobs)->toBe(3)
        ->and(DB::table('jobs')->count())->toBe(3);

    expect($import->refresh()->status)->toBe(ImportStatus::Processing);
});

it('dispatches a real batch of mill-processing job chains without crashing on closure serialization', function () {
    $import = makeArcGisImport([georgiaFeature(1, 'Mill A')]);

    $rawImport = MillRawImport::create([
        'import_id'      => $import->id,
        'raw_feature_id' => '1',
        'geojson'        => georgiaFeature(1, 'Mill A'),
        'status'         => 'pending',
    ]);

    Mill::create([
        'mill_name'          => 'Mill A',
        'state_id'           => $import->state_id,
        'import_id'          => $import->id,
        'mill_raw_import_id' => $rawImport->id,
        'status'             => 'pending',
    ]);

    (new QueueMillProcessingJobs($import->id))->handle();

    expect(DB::table('job_batches')->count())->toBe(1)
        ->and(DB::table('jobs')->count())->toBeGreaterThan(0);
});
