<?php

namespace App\Imports;

use App\Models\Mill;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Row;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportCompleteEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowProcessedEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowSkippedEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportStartedEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Interfaces\WithCrudSupport;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;
use Exception;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Override;

class FloridaMills implements OnEachRow, ToModel, WithHeadingRow, WithCrudSupport, WithEvents
{
    protected $import_log;
    protected ?array $rules;

    #[Override]
    public function __construct(int $import_log_id, ?string $validator = null)
    {
        //Find the import log
        $model = config('backpack.operations.import.import_log_model') ?? ImportLog::class;
        $import_log = $model::find($import_log_id);
        if (!$import_log) {
            throw new Exception(__('import-operation::import.cant_find_log'));
        }
        $this->import_log = $import_log;
        $this->rules = $validator ? (new $validator)->rules() : null;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // dd($row);
        Log::debug('Importing from ' . $this->import_log->file_name . '...', ['row' => $row]);
        return new Mill([
            'match_id' => $row['match_id'],
            'mill_name' => $row['mill_name'],
        ]);
    }

    public function onRow(Row $row): void
    {
        $row = $row->toArray();
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $importer = $event->getConcernable();
                $log = $importer->getImportLog();
                ImportStartedEvent::dispatch($log);
            },
            AfterImport::class => function (AfterImport $event) {
                $importer = $event->getConcernable();
                $log = $importer->getImportLog();
                $log->completed_at = Carbon::now();
                $log->save();

                if ($log->delete_file_after_import) {
                    Storage::disk($log->disk)->delete($log->file_path);
                }

                ImportCompleteEvent::dispatch($log);
            },
        ];
    }
}
