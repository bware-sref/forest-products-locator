<?php

namespace App\Imports;

use App\Enums\PublicationStatus;
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
use Maatwebsite\Excel\Validators\Failure;
use Override;
use Throwable;

/**
 * CrudImport already implements the following concerns
 *  OnEachRow, ToModel, WithHeadingRow, WithCrudSupport, WithEvents
 */
// class MillsCrudImport extends CrudImport implements ShouldQueue, SkipsEmptyRows, WithChunkReading, SkipsOnFailure, SkipsOnError, WithValidation
/**
 * without SkipsEmptyRows
 * 
 */
// class MillsCrudImport extends CrudImport implements ShouldQueue, WithChunkReading, SkipsOnFailure, SkipsOnError, WithValidation
/**
 * Without ShouldQueue
 */
class MillsCrudImport extends CrudImport implements SkipsEmptyRows, WithChunkReading, SkipsOnFailure, SkipsOnError, WithValidation
{
    use RemembersRowNumber;
    use SkipsErrors;
    use SkipsFailures;

    protected $bulkRules = [];
    protected $bulkMessages = [];
    protected $bulkAttributes = [];

    protected $mappedRules = [];
    protected $bulkMappedRules = [];
    protected $bulkMappedAttributes = [];

    /**
     * Keys are DB fields
     * Values are mapped import column headings
     * 
     * @var array
     */
    protected $fieldMap = [];

    public function __construct(int $import_log_id, ?string $validator = null)
    {
        // Log::debug('CustomCrud, validator is huh?', ['validator' => $validator]);
        if ($validator !== ImportMillRequest::class) {
            $oldValidator = $validator;
            $validator = ImportMillRequest::class;
            Log::debug(self::class . '::__construct(), changing validator.', [
                'validator' => $validator,
                'oldValidator' => $oldValidator,
            ]);
        }

        //Find the import log
        // $model = config('backpack.operations.import.import_log_model') ?? ImportLog::class;
        // $import_log = $model::find($import_log_id);
        // if (!$import_log) {
        //     throw new Exception(__('import-operation::import.cant_find_log'));
        // }
        // $this->import_log = $import_log;
        // $this->rules = $validator ? (new $validator)->rules() : null;

        /**
         * Use parent constructor to set up and fetch import_log
         */
        parent::__construct($import_log_id, $validator);

        /**
         * import_log->config will be empty if we didn't go through MapFields before arriving here.
         * Right!
         * That's why I added makeConfig() and crudlate().
         */
        if (empty($this->import_log->config)) {
            Log::debug(self::class . "::__construct(), making config for import #{$this->import_log->id} because it was empty.");
            $this->import_log->config = $this->makeConfig();
            // Log::debug('import_log->config: ', ['config' => $this->import_log->config]);
            $this->import_log->save();
        }
        
        /**
         * This is essentially the same as the original since we set $validator to ImportMillRequest.
         */
        if (empty($this->rules)) {
            /**
             * We should just be able to do (new $validator())->rules();
             */
            $this->rules = (new ImportMillRequest())->rules();
            // Log::debug('CustomCrud: adding rules where there were none.', ['rules' => $this->rules]);
        } else {
            // Log::debug('we have rules: ', ['rules' => $this->rules]);
        }
        /**
         * This is where we should create mappedRules if we even need to
         */
    }

    protected function makeConfig(): array
    {
        Log::debug(self::class ."::makeConfig() is happening for import #{$this->import_log->id}.", [
            'when' => now()->format('Y-m-d H:i:s.v'),
        ]);
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

    /**
     * Implement OnEachRow
     * @param Row $row
     * @return void
     */
    public function onRow(Row $row): void
    {
        $rowIndex = $row->getIndex();
        // Log::debug(self::class.'::onRow(), #'.$rowIndex.': allegedly Row::toArray() causes prepareForValidation() to execute.', [
        //     'if so, we should see that here' => 'dunno',
        //     'when' => Carbon::now()->format('Y-m-d H:i:s.v'),
        // ]);


        $rowArray = $row->toArray();

        // Log::debug(self::class.'::onRow(), #'.$rowIndex, [
        //     'isThisBefore isWhenEmpty?' => 'dunno',
        //     'when' => Carbon::now()->format('Y-m-d H:i:s.v'),
        // ]);

        /**
         * Freshen the import_log
         * Use refresh() because fresh() returns a separate instance which must be assigned to a variable.
         */
        $this->import_log->refresh();

        // getRowNumber() does nothing!
        // $currentRow = $this->getRowNumber();

        /**
         * We don't need this empty check if we use SkipsEmptyRows
         * However, as demonstrated by the above Log output, SkipsEmptyRows causes the row to be skipped before we enter this method.
         * That probably only matters if we need to know why we skipped an empty row.
         * Well, mostly I wanted to be able to identify and count skipped rows, but that's not entirely necessary.
         */
        if (collect($rowArray)->filter()->isEmpty()) {
            Log::debug(self::class.'::onRow(), skipping empty row #'.$rowIndex);
            ImportRowSkippedEvent::dispatch($this->import_log, $rowArray);
            return;
        }

        //Get the current model entry based on the primary key field
        $entry = $this->getEntry($rowArray);

        
        //Filter the spreadsheet row down to mapped columns, exclude the primary key
        /**
         * make sure import_log->config isn't empty before attempting to filter
         */
        if (!empty($this->import_log->config)) {
            $rowArray = $this->filterRow($rowArray);
        } else {
            // Log::debug('import_log->config is empty!', ['import_log' => $this->import_log]);
        }
        // FFS, what does the import config do if not map spreadsheet columns to db fields?

        //If validation is set, we need to map the file columns to our model fields
        /**
         * For some reason, our only rule is for "file"
         * That's because the original validation ends up being for the file upload rather than the import itself.
         * We should have proper validation rules now.
         * However, prepareForValidation() has already been executed and in fact, this mofo is acting like...a jerk?
         * Did validation also already run, or just prepareForValidation()?
         * This could be called mapValidationRules() + soft validation
         * And hold the phone, this shit should only run once for the import, not on every row.
         */
        if ($this->rules) {
            // Log::debug(self::class.'::onRow(), Import row: we have rules!', ['rules' => $this->rules]);

            $mapped_rules = [];
            foreach ($this->rules as $key => $rule) {
                $matching_heading = $this->getMatchedHeading($key);
                if ($matching_heading) {
                    $mapped_rules[$matching_heading] = $rule;
                }
            }

            // if (count($mapped_rules) > 0 && Validator::make($row, $mapped_rules)->fails()) {
            if (\count($mapped_rules) > 0) {
                // dump('mappedRules');
                // dd($mapped_rules);

                // Log::debug('We have mapped_rules!', ['mappedRules' => $mapped_rules]);

                /**
                 * FYI, using Validator::make() to perform validation allows validating without throwing exceptions.
                 */
                $val = Validator::make($rowArray, $mapped_rules);
                if ($val->fails()) { // Validator::make($row, $mapped_rules)->fails()) {
                    // $this->import_log->failed_rows += 1;
                    /**
                     * Umm...weren't we moving this to the event handler?
                     * Yeah, it's there, and confirmed to be working.
                     */
                    // $this->import_log->increment('failed_rows');
                    // $this->import_log->save();

                    Log::debug('Import row '.$rowIndex.' failed: ', [
                        'row' => $rowArray,
                        // 'mapped_rules' => $mapped_rules,
                        'invalid' => $val->invalid(),
                        'failed' => $val->failed(),
                        'messages' => $val->messages(),
                        'failedRows' => $this->import_log->failed_rows,
                    ]);

                    ImportRowSkippedEvent::dispatch($this->import_log, $rowArray);
                    return;
                }
            } else {
                // Log::debug('No mapped_rules?!?', ['rules' => $this->rules, 'rowKeys' => array_keys($rowArray)]);
            }
        }

        /**
         * This could be called mapHeadingsToFields()
         * It also probably only needs to be run once instead of for every row.
         * Well, much of it only needs to run once per import.
         * Mapping the headings to configs, and the configs to handler_classses seems like it only needs to happen once per import.
         * 
         * Loop through row headings to map import data to DB columns.
         */
        foreach ($rowArray as $heading => $value) {
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
                // Log::debug(self::class.'::onRow(): we have matched_config and handler_classes for row #'.$rowIndex, [
                //     'matched_config' => $matched_config,
                //     'how many handlers?' => \count($handler_classes),
                // ]);
                foreach ($handler_classes as $index => $handler_class) {
                    //Instantiate handler class, process data from column
                    $handler = new $handler_class($value, $matched_config[$index], $this->import_log->model);
                    $data = $handler->output();

                    //Assign the data to the model field specified in config
                    $model_field = $matched_config[$index]['name'];
                    $entry->{$model_field} = $data;
                }
            }
        }
        /**
         * Lastly, add the import_id and the user_id!
         */
        $entry->import_id = $this->import_log->id;
        $entry->user_id = $this->import_log->user_id;

        /**
         * Even more lastly, see if the import has a state_id
         * If so, add it to the entry.
         * Actually, the default is null for both, so we can just assign whatever the value is.
         */
        $entry->state_id = $this->import_log->state_id;

        /**
         * More lastly yet, set status to pending because we still need to do more processing in the background.
         * Model relationships, location verification and/or completion.
         */
        $entry->status = PublicationStatus::Pending;

        //Save the entry
        $entry->save();

        Log::debug(self::class."::onRow(): entry #{$entry->id} created for \"{$entry->mill_name}\" from row #".($rowIndex).".", [
            'imported_so_far (not counting this one)' => $this->import_log->imported_rows,
        ]);

        ImportRowProcessedEvent::dispatch($this->import_log, $entry, $rowArray);
    }

    /**
     * Implement WithEvents
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
            /**
             * I get that this is an event handler, but it still seems a weird way to get the importer class from within
             * the importer class.
             * That said, I suppose that because this is an event handler, then it is probably executed outside of this context.
             */
            $importer = $event->getConcernable();
            $log = $importer->getImportLog();

            /**
             * @var Reader $reader
             */
            $reader = $event->getDelegate();
            $totalRows = array_reduce($reader->getTotalRows(), fn($carry, $item) => $carry + $item, 0);
            if ($importer instanceof WithHeadingRow) {
                $totalRows--;
            }
            // $spreadsheet = $reader->getDelegate();
            // $props = $spreadsheet->getProperties();
            Log::debug(self::class . '::BeforeImport() for ImportLog #'.$log->id.': ', [
                'totalRows' => $totalRows,
                'importClass' => class_basename($importer),
                'when' => now()->format('Y-m-d H:i:s.v'),
                'rawTotalRows' => $reader->getTotalRows(),
                // 'props' => print_r($props, true),
            ]);
            $log->total_rows = $totalRows;
            $log->save();

            ImportStartedEvent::dispatch($log);
        };


        /**
         * Hold the phone, ImportFailed doesn't extend Event...
         * We should probably just make our own events for the things that are missing, incomplete, or insufficient.
         */
        $events[ImportFailed::class] = function (ImportFailed $event) {
            Log::debug('Import failed, but we don\'t have a way to identify it, except via the import_log member...', [
                'event' => $event,
                'importId' => $this->import_log->id,
                'originalFile' => $this->import_log->original_file_name,
                'startedAt' => $this->import_log->started_at,
            ]);
        };

        /**
         * After Chunk!
         */
        $events[AfterChunk::class] = function (AfterChunk $event) {
            /**
             * I don't know why we would need to, but we can use $event->getConcernable() to fetch the importer if that seems useful.
             */
            $importer = $event->getConcernable();
            $log = $importer->getImportLog();
            // $reader = $event->getDelegate();

            Log::debug(self::class.'::AfterChunkHandler() starting on row #' . $event->getStartRow(), [
                'importId' => $log->id,
                'processedRows' => $log->processed_rows,
                'importedRows' => $log->imported_rows,
                'failed(orSkipped)Rows' => $log->failed_rows,
                'updatedAt' => $log->updated_at,
                // 'importId' => $this->import_log->id,
                // 'processedRows' => $this->import_log->processed_rows,
                // 'failed(orSkipped)Rows' => $this->import_log->failed_rows,
                // 'updatedAt' => $this->import_log->updated_at,
            ]);
        };

        $events[AfterImport::class] = function (AfterImport $event) {
            /**
             * Okay!
             * Turns out that Excel::toArray() dispatches an AfterImport event.
             * Hilariously, there doesn't seem to be a corresponding BeforeImport event.
             * 
             * On the other hand, we could use that to determine whether or not to proceed with deleting
             * other mills in this state (if that's what we're actually spozta do).
             * Actually, we perhaps should not delete the old mills before fully processing these.
             * By fully processing, I mean adding the model relationships and fleshing out the location data.
             * Fleshing out the location data is especially important because the state data is poorly formatted and sometimes wrong.
             * So, to recap, even if we're spozta delete old records from this state, we need to keep processing this data first.
             * That seems like it actually makes the whole damn thing easier.
             * I could be wrong though.
             * In any case, what needs to happen now is that we set status = pending for all the mills we import here.
             * Then, once the import actually completes (as evidenced by having both started_at and completed_at), we can 
             * queue a job to finish filling in the data for the mills in this import.
             * After that job finishes, then we can delete old mills from this state.
             * Start a transaction, delete mills in this state with import_id not null (meaning the mill was added manually 
             * instead of as part of an import) and lower than this import_id, then update mills.status for this import to 
             * approved.
             */    

            $importer = $event->getConcernable();
            $log = $importer->getImportLog();
            $log->completed_at = Carbon::now();
            $log->save();

            if ($log->delete_file_after_import) {
                Storage::disk($log->disk)->delete($log->file_path);
            }

            Log::debug(self::class.'::AfterImportHandler() event!', [
                'event' => $event,
                'importLogId' => $log->id,
                'startedAt' => $log->started_at,
                'completedAt' => $log->completed_at,
                'processedRows' => $log->processed_rows,
                'failedRows' => $log->failed_rows,
                'updatedAt' => $log->updated_at,
                'stateId' => $log->state_id,
                'delete_from_state' => $log->delete_from_state,
            ]);

            ImportCompleteEvent::dispatch($log);
        };

        return $events;
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
     * I forget why I thought I needed this method.
     * Was it because initially using a custom import would preclude mapping?
     */
    protected function crudulate(string $key, int $priority = 0): array
    {
        $label = Str::ucwords(Str::replace('_', ' ', $key));
        $format = '{"name":"%1$s","label":"%3$s","type":"text","key":"%1$s","priority":%2$d,"tableColumn":true,"orderable":true,"searchLogic":true}';
        $json = \sprintf($format, $key, $priority, $label);
        return [json_decode($json, associative: true)];
    }

    /**
     * Extend empty rows logic
     * isEmptyWhen() is apparently triggered by Excel::toArray().
     */
    public function isEmptyWhen(array $row): bool
    {
        // Log::debug(self::class.'::isEmptyWhen()', [
        //     'when' => now()->format('Y-m-d H:i:s.v'),
        // ]);

        /**
         * We can't require match_id because it simply doesn't exist outside our system.
         */
        $isEmpty = empty(array_filter($row)); // || empty($row['mill_name']);
        if ($isEmpty) {
            Log::debug(self::class.'::isEmptyWhen() no idea what row or anything because madness.', [
                'row' => $row,
                'when' => Carbon::now()->format('Y-m-d H:i:s.v'),
            ]);
        }
        return $isEmpty;
        // return empty($row['match_id']);
    }

    /**
     * Removes newline characters from haystack and replaces them with replacement
     * Also trims whitespace before and after removing new lines.
     * @param string $haystack
     * @param string $replacement
     * @return string
     */
    public function replaceNewlines(string $haystack, string $replacement = "|"): string
    {
        return Str::trim(Str::replace("\n", $replacement, Str::trim($haystack)));
    }

    /**
     * Implment SkipsOnError
     * @param Throwable $e
     * @return void
     */
    public function onError(Throwable $e)
    {
        // parent::onError($e);
        $this->errors[] = $e;
        Log::debug('MillsCrudImport::onError()!', [
            'importId' => $this->import_log->id,
            'error' => $e,
        ]);
    }

    /**
     * Implement SkipsOnFailure
     * @param Failure[] $failures
     * @return void
     */
    public function onFailure(Failure ...$failures): void
    {
        Log::debug('MillsCrudImport::onFailure()', [
            'importId' => $this->import_log->id,
            'failures' => print_r($failures, true),
        ]);
    }

    /**
     * Optional when implementing WithValidation
     * 
     * BTW, this method gets called multiple times per row.
     * Some of those calls result from the Preview step we added.
     * The onRow() method causes it to execute, as does the Import::toArray() method.
     * 
     * @param array $data
     * @param int $rowIndex
     * @return array
     */
    public function prepareForValidation(array $data, int $rowIndex): array
    {
        // $millName = $this->mapField('mill_name');

        /**
         * When we actually execute the import, something causes prepareForValidation() to be executed twice for each row.
         */
        // Log::debug(self::class.'::prepareForValidation(): import #'.$this->import_log->id.', row #'.$rowIndex, [
        //     'millName' => $millName,
        //     'mill_name' => $data[$millName] ?? $millName . ' is null?!?',
        //     // 'importId' => $this->import_log->id,
        //     // 'data' => $data,
        //     // 'rowIndex' => $rowIndex,
        //     'when' => now()->format('Y-m-d H:i:s.v'),
        // ]);

        /**
         * Bail if none of the other special fields need attention.
         * FFS, turns out zip codes might need to be cast to strings
         */
        // if (empty($data['type']) && empty($data['species']) && empty($data['web_site']) && !empty($data['match_id']) &&
        //     empty($data['physical_zip']) && empty($data['mailing_zip'])
        // ) {
        if (empty($data[$this->mapField('type')]) && // may need new lines removed
            empty($data[$this->mapField('species')]) && // may need new lines removed
            empty($data[$this->mapField('web_site')]) && // may need http://
            empty($data[$this->mapField('physical_zip')]) && // may need to be cast to string
            empty($data[$this->mapField('mailing_zip')])  && // may need to be cast to string
            empty($data[$this->mapField('latitude')]) &&    // may need to be cast to string
            empty($data[$this->mapField('longitude')]) &&  // may need to be cast to string
            empty($data[$this->mapField('physical_address')]) && // we may need to fill in "SAME" values, a la Florida's data
            empty($data[$this->mapField('mailing_address')])
        ) {
            Log::debug('MillsCrudImport::prepareForValidation(): no fields need attention.', [
                'rowIndex' => $rowIndex,
            ]);
            return $data;
        }

        /**
         * Not sure if this is even needed anymore since applying the trimCells middleware
         */
        foreach (['type', 'species'] as $field) {
            $field = $this->mapField($field);
            if (empty($data[$field])) {
                continue;
            }
            $data[$field] = $this->replaceNewlines($data[$field]);
        }

        $webSite = $this->mapField('web_site');
        if (!empty($data[$webSite]) && 
            /**
             * If website doesn't even resemble a URL, we shouldn't prepend http://.
             * Or does it even matter?
             * If it's invalid with http:// it's also invalid without it.
             */
            // parse_url($data[$webSite], PHP_URL_HOST) &&
            Str::doesntStartWith($data[$webSite], ['http://', 'https://'])) {
            $data[$webSite] = 'http://' . $data[$webSite];
        }

        /**
         * Cast zips if present
         */
        foreach (['physical_zip', 'mailing_zip', 'latitude', 'longitude'] as $field) {
            $field = $this->mapField($field);
            if (!empty($data[$field]) && !\is_string($data[$field])) {
                $data[$field] = (string) $data[$field];
            }
        }

        /**
         * if physical_address is populated, and it contains 'same', we need to overwrite that value with mailing_address
         */
        if (!empty($data[$this->mapField('physical_address')]) && !empty($data[$this->mapField('mailing_address')])) {
            $paKey = $this->mapField('physical_address');
            $maKey = $this->mapField('mailing_address');
            $phys = Str::of($data[$paKey])->trim();
            $mail = Str::of($data[$maKey])->trim();
            if ('same' === $phys->lower()->toString()) {
                $data[$paKey] = $data[$maKey];
                Log::debug(self::class." filling in same physical address for Mill '{$data[$this->mapField('mill_name')]}'", [
                    'phys' => $phys->toString(),
                    'physical_address' => $data[$paKey],
                    'mailing_address' => $data[$maKey],
                ]);
            } else if ('same' === $mail->lower()->toString()) {
                $data[$maKey] = $data[$paKey];
                Log::debug(self::class." filling in same mailing address for Mill '{$data[$this->mapField('mill_name')]}'", [
                    'mail' => $mail->toString(),
                    'physical_address' => $data[$paKey],
                    'mailing_address' => $data[$maKey],
                ]);
            }
        }
        // Log::debug('After CustomCrudImport::prepareForValidation(): ', ['data' => $data, 'row' => $rowIndex]);
        return $data;
    }

    /**
     * Implement WithValidation
     * @return array
     */
    public function rules(): array
    {
        return $this->rules ?? [];
    }

    protected function bulkUp(array $data): array
    {
        $bulk = [];
        foreach ($data as $k => $v) {
            if (Str::doesntStartWith($k, '*.')) {
                $k = '*.'.$k;
            }
            $bulk[$k] = $v;
        }
        return $bulk;
    }

    public function bulkRules(): array
    {
        if (empty($this->bulkRules) && !empty($this->rules)) {
            // foreach ($this->rules as $k => $rule) {
            //     if (Str::doesntStartWith('*.', $k)) {
            //         $k = '*.'.$k;
            //     }
            //     $this->bulkRules[$k] = $rule;
            // }
            $this->bulkRules = $this->bulkUp($this->rules);
        }
        return $this->bulkRules;
    }

    public function bulkMessages(): array
    {
        if (empty($this->bulkMessages)) {
            foreach ($this->bulkRules() as $k => $v) {
            // foreach ($this->rules() as $k => $v) {
                $this->bulkMessages[$k] = 'There is an issue with :attribute on row #:position.';
            }
        }
        return $this->bulkMessages;
    }

    public function bulkAttributes(): array
    {
        if (empty($this->bulkAttributes)) {
            foreach ($this->bulkRules() as $k => $rule) {
                $this->bulkAttributes[$k] = Str::ucwords(Str::replace(['*.', '_'], ['', ' '], $k));
            }
        }
        return $this->bulkAttributes;
    }

    /**
     * Holy mole.
     * This method was created to handle prepareForValidation() being invoked before we hit onRow()
     * However, mapping when prepareForValidation executes results in the mapped validation in onRow() having the wrong fields.
     * It's a pickle.
     * 
     * @param array $row
     * @return array
     */
    protected function mapRowKeysToDBColumns(array $row): array
    {
        if (empty($this->import_log->config)) {
            // do something drastic?
            Log::error(self::class.'::mapRowKeysToDBColumns() invoked without an import_log->config!');
            return $row;
        }
        $configs = collect($this->import_log->config);
        // $ssKeys = array_keys($row);
        $mapped = [];
        foreach ($row as $heading => $value) {
            if (empty($configs[$heading])) {
                Log::debug(self::class.'::mapRowKeysToDBColumns(): No config for '. $heading);
                continue;
            }
            /**
             * FFS, these damn things are nested arrays
             * numeric index wrapping a
             */
            $config = $configs[$heading];
            while (\is_array($config) && isset($config[0])) {
                $config = $config[0];
            }

            $mapped[$config['name']] = $value;
        }
        return $mapped;
    }

    /**
     * Input a DB field, get a mapped column heading back
     * @param string $field
     * @return string
     */
    public function mapFieldToHeading(string $field): string
    {
        if (empty($this->fieldMap[$field])) {
            // if nothing else found, use the original field name
            $this->fieldMap[$field] = $this->getMatchedHeading($field) ?? $field;
        }
        return $this->fieldMap[$field]; // $this->getMatchedHeading($field) ?? $field;
    }

    public function mapField(string $field): string
    {
        return $this->mapFieldToHeading($field);
    }

    public function fieldMap(): array
    {
        if (empty($this->fieldMap) || \count($this->fieldMap) < \count($this->rules)) {
            // dd($this->rules());
            foreach ($this->rules as $k => $v) {
                $this->fieldMap[$k] = $this->getMatchedHeading($k) ?? $k;
            }
        }
        return $this->fieldMap;
    }

    public function mappedRules(): array
    {
        if (empty($this->mappedRules) && !empty($this->rules)) {
            foreach ($this->rules as $key => $rule) {
                $matchedHeading = $this->getMatchedHeading($key);
                if (!empty($matchedHeading)) {
                    $this->mappedRules[$matchedHeading] = $rule;
                }
            }
        }
        return $this->mappedRules;
    }

    public function bulkMappedRules(): array
    {
        if (empty($this->bulkMappedRules) && !empty($this->mappedRules())) {
            // foreach ($this->mappedRules as $key => $rule) {
            //     if (Str::doesntStartWith('*.', $key)) {
            //         $key = '*.' . $key;
            //     }
            //     $this->bulkMappedRules[$key] = $rule;
            // }
            $this->bulkMappedRules = $this->bulkUp($this->mappedRules());
        }
        return $this->bulkMappedRules;
    }

    public function bulkMappedAttributes(): array
    {
        if (empty($this->bulkMappedAttributes) && !empty($this->bulkMappedRules())) {
            foreach ($this->bulkMappedRules() as $k => $rule) {
                $this->bulkMappedAttributes[$k] = Str::ucwords(Str::replace(['*.', '_'], ['', ' '], $k));
            }
        }
        return $this->bulkMappedAttributes;
    }
}
