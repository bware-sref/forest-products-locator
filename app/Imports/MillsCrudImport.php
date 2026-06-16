<?php

namespace App\Imports;

use App\Http\Requests\ImportMillRequest;
use App\Models\Mill;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterChunk;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Reader;
use Maatwebsite\Excel\Row;
use RedSquirrelStudio\LaravelBackpackImportOperation\Imports\CrudImport;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportCompleteEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowProcessedEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowSkippedEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportStartedEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Interfaces\WithCrudSupport;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;
use Exception;
use Override;
use Throwable;


// OnEachRow, ToModel, WithHeadingRow, WithCrudSupport, WithEvents
class MillsCrudImport extends CrudImport implements ShouldQueue, SkipsEmptyRows, WithChunkReading, SkipsOnFailure, SkipsOnError, WithValidation
{
    use RemembersRowNumber;
    use SkipsErrors;
    use SkipsFailures;

    // protected $import_log;
    // protected ?array $rules;

    // #[Override]
    public function __construct(int $import_log_id, ?string $validator = null)
    {
        Log::debug('CustomCrud, validator is huh?', ['validator' => $validator]);
        if ($validator !== ImportMillRequest::class) {
            $oldValidator = $validator;
            $validator = ImportMillRequest::class;
            Log::debug('CustomCrud, changing validator from "'.$oldValidator.'" to "'.$validator.'"', ['validator' => $validator]);
        }
        //Find the import log
        // $model = config('backpack.operations.import.import_log_model') ?? ImportLog::class;
        // $import_log = $model::find($import_log_id);
        // if (!$import_log) {
        //     throw new Exception(__('import-operation::import.cant_find_log'));
        // }
        // $this->import_log = $import_log;
        // $this->rules = $validator ? (new $validator)->rules() : null;
        parent::__construct($import_log_id, $validator);

        if (empty($this->import_log->config)) {
            $this->import_log->config = $this->makeConfig();
            Log::debug('import_log->config: ', ['config' => $this->import_log->config]);
            $this->import_log->save();
        }
        
        if (empty($this->rules)) {
            $this->rules = (new ImportMillRequest())->rules();
            Log::debug('CustomCrud: adding rules where there were none.', ['rules' => $this->rules]);
        } else {
            Log::debug('we have rules: ', ['rules' => $this->rules]);
        }
    }

    protected function makeConfig(): array
    {
        /**
         * The keys should correspond to spreadsheet columns?
         * Or is it database columns?
         * keys correspond to spreadsheet columns
         * the "name" member corresponds to db column (unless "key" does...)
         */
        $columns = Mill::IMPORT_COLUMNS;
        $config = [];
        $priority = 0;
        foreach ($columns as $heading => $dbColumn) {
            $config[$heading] = $this->crudulate($dbColumn, $priority++);
        }
        return $config;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // dd($row);
        Log::debug('in model(), importing from ' . $this->import_log->file_name . '...', [
            'row' => $row,
            'import_log' => $this->import_log,
        ]);
        /**
         * Do we get States, MillTypes and WoodSpecies?
         * We can do that later, if need be.
         * The one thing we need to do is change the key 'county' to 'county_name' and unset 'county'
         */
        if (!empty($row['county'])) {
            $row['county_name'] = $row['county'];
            unset($row['county']);
        }

        $row['species'] = $this->replaceNewlines($row['species'] ?? ''); //Str::trim(Str::replace("\n", "|", Str::trim($row['species'] ?? '')));
        $row['type'] = $this->replaceNewlines($row['type']); // Str::trim(Str::replace("\n", "|", Str::trim($row['type'] ?? '')));

        Log::debug('in model()(still), modified row as shown:', ['row' => $row]);

        return new Mill($row);
        // return new Mill([
        //     'match_id' => $row['match_id'],
        //     'mill_id' => $row['mill_id'],
        //     'mill_name' => $row['mill_name'],
        //     'latitude' => $row['latitude'],
        // ]);
    }

    public function onRow(Row $row): void
    {
        $rowIndex = $row->getIndex();
        $row = $row->toArray();

        // getRowNumber() does nothing!
        $currentRow = $this->getRowNumber();

        if (collect($row)->filter()->isEmpty()) {
            Log::debug('skipping empty row #'.$rowIndex);
            ImportRowSkippedEvent::dispatch($this->import_log, $row);
            return;
        }

        if (empty($row['match_id']) || empty($row['mill_name'])) {
            Log::debug('skipping empty match_id or mill_name on row #'.$rowIndex);
            ImportRowSkippedEvent::dispatch($this->import_log, $row);
            return;
        }

        Log::debug('in onRow(), importing from ' . $this->import_log->file_name . '...', [
            'currentRow' => $currentRow,
            'rowIndex' => $rowIndex,
            "\n",
            'row' => $row,
            // 'import_log' => $this->import_log,
        ]);

        //Get the current model entry based on the primary key field
        $entry = $this->getEntry($row);
        //Filter the spreadsheet row down to mapped columns, exclude the primary key
        /**
         * make sure import_log->config isn't empty before attempting to filter
         */
        if (!empty($this->import_log->config)) {
            $row = $this->filterRow($row);
        } else {
            // Log::debug('import_log->config is empty!', ['import_log' => $this->import_log]);
        }

        //If validation is set, we need to map the file columns to our model fields
        /**
         * For some reason, our only rule is for "file"
         */
        if ($this->rules) {
            // Log::debug('Import row: we have rules!', ['rules' => $this->rules]);

            $mapped_rules = [];
            foreach ($this->rules as $key => $rule) {
                $matching_heading = $this->getMatchedHeading($key);
                if ($matching_heading) {
                    $mapped_rules[$matching_heading] = $rule;
                }
            }

            // if (count($mapped_rules) > 0 && Validator::make($row, $mapped_rules)->fails()) {
            if (count($mapped_rules) > 0) {
                $val = Validator::make($row, $mapped_rules);
                if ($val->fails()) { // Validator::make($row, $mapped_rules)->fails()) {
                    Log::debug('Import row '.$currentRow.' failed: ', [
                        'row' => $row,
                        // 'mapped_rules' => $mapped_rules,
                        'invalid' => $val->invalid(),
                        'failed' => $val->failed(),
                        'messages' => $val->messages(),
                    ]);
                    ImportRowSkippedEvent::dispatch($this->import_log, $row);
                    return;
                }
            }
        }

        //Loop through row headings
        foreach ($row as $heading => $value) {
            $data = null;
            //Get the config that matches the current column heading
            $matched_config = $this->getMatchedConfig($heading);
            $handler_classes = $this->getColumnHandlerClasses($matched_config);

            /**
             * fallback value for model_field and data
             */
            $model_field = $heading;
            $data = $value;

            if ($matched_config && \count($handler_classes) === \count($matched_config)) {
                foreach ($handler_classes as $index => $handler_class) {
                    //Instantiate handler class, process data from column
                    $handler = new $handler_class($value, $matched_config[$index], $this->import_log->model);
                    $data = $handler->output();

                    //Assign the data to the model field specified in config
                    $model_field = $matched_config[$index]['name'];
                    $entry->{$model_field} = $data;
                }
            }
            /**
             * Replace any newlines in type or species values with | or some such
             * We may not need this anymore because I added prepareForValidation() to the FormRequest...
             */
            if (\in_array($model_field, ['type', 'species']) && Str::contains($data, "\n")) {
                Log::debug('Fixing '.$model_field.' because it contains new lines.', ['data' => $data]);
                $entry->{$model_field} = $this->replaceNewlines($data); // Str::trim(Str::replace("\n", '|', Str::trim($data)));
            } else {
                // Log::debug('model_field "'.$model_field.'" not type or species...', ['data' => $data]);
            }
        }
        //Save the entry
        $entry->save();
        ImportRowProcessedEvent::dispatch($this->import_log, $entry, $row);
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        $events = parent::registerEvents();

        /**
         * Override the parent BeforeImport listener
         * we want to grab properties of the file we're working with to help us understand how well (or not) the import goes.
         */
        $events[BeforeImport::class] = function (BeforeImport $event) {
            $importer = $event->getConcernable();
            $log = $importer->getImportLog();

            /**
             * @var Reader $reader
             */
            $reader = $event->getDelegate();
            $totalRows = array_reduce($reader->getTotalRows(), fn($carry, $item) => $carry + $item, 0);
            // $spreadsheet = $reader->getDelegate();
            // $props = $spreadsheet->getProperties();
            Log::debug('BeforeImport for ImportLog #'.$log->id.': ', [
                'totalRows' => $totalRows,
                'importClass' => class_basename($importer),
                // 'props' => print_r($props, true),
            ]);

            ImportStartedEvent::dispatch($log);
        };


        /**
         * Hold the phone, ImportFailed doesn't extend Event...
         * We should probably just make our own events for the things that are missing, incomplete, or insufficient.
         */
        $events[ImportFailed::class] = function (ImportFailed $event) {
            Log::debug('Import failed, but we don\'t have a way to identify it, except via the import_log member...', [
                'event' => $event,
                'import_log' => $this->import_log,
            ]);
        };

        /**
         * After Chunk!
         */
        $events[AfterChunk::class] = function (AfterChunk $event) {
            Log::debug('AfterChunk starting on row #' . $event->getStartRow());
        };

        $events[AfterImport::class] = function (AfterImport $event) {
            $importer = $event->getConcernable();
            $log = $importer->getImportLog();
            $log->completed_at = Carbon::now();
            $log->save();

            if ($log->delete_file_after_import) {
                Storage::disk($log->disk)->delete($log->file_path);
            }

            ImportCompleteEvent::dispatch($log);
        };


        return $events;

        // return [
        //     BeforeImport::class => function (BeforeImport $event) {
        //         $importer = $event->getConcernable();
        //         $log = $importer->getImportLog();
        //         ImportStartedEvent::dispatch($log);
        //     },
        //     AfterImport::class => function (AfterImport $event) {
        //         $importer = $event->getConcernable();
        //         $log = $importer->getImportLog();
        //         $log->completed_at = Carbon::now();
        //         $log->save();

        //         if ($log->delete_file_after_import) {
        //             Storage::disk($log->disk)->delete($log->file_path);
        //         }

        //         ImportCompleteEvent::dispatch($log);
        //     },
        // ];
    }

    /**
     * Implments WithChunkReading
     */
    public function chunkSize(): int
    {
        return 1; // config('backpack.operations.import.chunk_size') ?? 100;
    }

    /**
     * Fakes import config JSON for a single column
     */
    protected function crudulate(string $key, int $priority = 0): array
    {
        $label = Str::ucwords(Str::replace('_', ' ', $key));
        $format = '{"name":"%1$s","label":"%3$s","type":"text","key":"%1$s","priority":%2$d,"tableColumn":true,"orderable":true,"searchLogic":true}';
        $json = sprintf($format, $key, $priority, $label);
        return [json_decode($json, associative: true)];
    }

    /**
     * Extend empty rows logic
     */
    public function isEmptyWhen(array $row): bool
    {
        return is_null($row['match_id']) || empty($row['match_id']);
    }

    public function replaceNewlines(string $haystack, string $replacement = "|"): string
    {
        return Str::trim(Str::replace("\n", $replacement, Str::trim($haystack)));
    }

    public function onError(Throwable $e)
    {
        // parent::onError($e);
        $this->errors[] = $e;
        Log::debug('Error during import: ', ['error' => $e]);
    }

    public function prepareForValidation(array $data, int $rowIndex): array
    {
        /**
         * Bail if none of the special fields have data.
         */
        if (empty($data['type']) && empty($data['species']) && empty($data['web_site'])) {
            return $data;
        }

        Log::debug('Before CustomCrudImport::prepareForValidation(): ', ['data' => $data, 'row' => $rowIndex]);

        foreach (['type', 'species'] as $field) {
            if (empty($data[$field])) {
                continue;
            }
            $data[$field] = $this->replaceNewlines($data[$field]);
        }

        if (!empty($data['web_site']) && Str::doesntStartWith($data['web_site'], ['http://', 'https://'])) {
            $data['web_site'] = 'http://' . $data['web_site'];
        }

        Log::debug('After CustomCrudImport::prepareForValidation(): ', ['data' => $data, 'row' => $rowIndex]);
        return $data;
    }

    public function rules(): array
    {
        return $this->rules ?? [];
    }
}
