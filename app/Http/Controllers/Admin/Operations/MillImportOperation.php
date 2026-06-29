<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Requests\UploadImportFileRequest;
use App\Imports\MillsCrudImport;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\CrudPanel\Hooks\Facades\LifecycleHook;
use RedSquirrelStudio\LaravelBackpackImportOperation\Columns\NumberColumn;
use RedSquirrelStudio\LaravelBackpackImportOperation\Columns\TextColumn;
use RedSquirrelStudio\LaravelBackpackImportOperation\Imports\CrudImport;
use RedSquirrelStudio\LaravelBackpackImportOperation\Imports\QueuedCrudImport;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;
use RedSquirrelStudio\LaravelBackpackImportOperation\Requests\ImportFileRequest;
use RedSquirrelStudio\LaravelBackpackImportOperation\Exceptions\PrimaryKeyNotFoundException;
use RedSquirrelStudio\LaravelBackpackImportOperation\ImportOperation as ImportOperation;
use Exception;

trait MillImportOperation
{
    use ImportOperation {
        ImportOperation::setupImportRoutes as baseSetupImportRoutes;
        ImportOperation::setupImportDefaults as baseSetupImportDefaults;
        ImportOperation::setupImportFileUpload as baseSetupImportFileUpload;
    }

    // protected ?string $example_file_url = null;
    // protected ?string $custom_import_handler = null;

    /**
     * Define which routes are needed for this operation.
     *
     * @param string $segment Name of the current entity (singular). Used as first URL segment.
     * @param string $routeName Prefix of the route name.
     * @param string $controller Name of the current CrudController.
     * @return void
     */
    protected function setupImportRoutes(string $segment, string $routeName, string $controller): void
    {
        /**
         * Step 1
         * Displays the "SelectFile" screen
         * Note that 'uses' points to the 'selectFile' method that this trait adds to the current CrudController.
         */
        Route::get($segment . '/import', [
            'as' => $routeName . '.import.selectFile',
            'uses' => $controller . '@selectFile',
            'operation' => 'import',
        ]);

        /**
         * Step 2
         * Catches the POST request from SelectFile
         */
        Route::post($segment . '/import', [
            'as' => $routeName . '.import.handleFile',
            'uses' => $controller . '@handleFile',
            'operation' => 'import',
        ]);

        /**
         * Step 3
         * displays the MapFields screen
         */
        Route::get($segment . '/import/{id}/map', [
            'as' => $routeName . '.import.mapFields',
            'uses' => $controller . '@mapFields',
            'operation' => 'import',
        ]);

        /**
         * Step 4
         * Catches the POST from MapFields
         */
        Route::post($segment . '/import/{id}/map', [
            'as' => $routeName . '.import.handleMapping',
            'uses' => $controller . '@handleMapping',
            'operation' => 'import',
        ]);

        /**
         * Step 5
         * Displays the ConfirmImport screen which actually confirms the mapping
         */
        Route::get($segment . '/import/{id}/confirm', [
            'as' => $routeName . '.import.confirmImport',
            'uses' => $controller . '@confirmImport',
            'operation' => 'import',
        ]);

        /**
         * Catches POST from ConfirmImport
         * Runs or Queues the Import
         */
        Route::post($segment . '/import/{id}/confirm', [
            'as' => $routeName . '.import.handleImport',
            'uses' => $controller . '@handleImport',
            'operation' => 'import',
        ]);

        /**
         * Add routes for previewing data
         */
        Route::get($segment . '/import/{id}/preview', [
            'as' => $routeName . '.import.preview',
            'uses' => $controller . '@preview',
            'operation' => 'import',
        ]);

        /**
         * I don't know that we need this route
         * If preview is successful, we just continue to /import/{id}/confirm
         */
        // Route::post($segment . '/import/{id}/preview', [
        //     'as' => $routeName . '.import.acceptPreview',
        //     'uses' => $controller . '@acceptPreview',
        //     'operation' => 'import',
        // ]);


    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     * @return void
     */
    protected function setupImportDefaults(): void
    {
        CRUD::allowAccess('import');
        CRUD::enableGroupedErrors();
        // CRUD::operation('import', function () {
        //     CRUD::loadDefaultOperationSettingsFromConfig();
        // });
        LifecycleHook::hookInto('import:before_setup', function () {
            CRUD::loadDefaultOperationSettingsFromConfig();
        });
        // CRUD::operation('list', function () {
        //     CRUD::addButton('top', 'import', 'view', 'import-operation::buttons.import_button');
        // });

        /**
         * Just to be clear, this hooks into the CrudController::list() method for the current Controller, e.g., MillCrudController.
         */
        LifecycleHook::hookInto('list:before_setup', function () {
            CRUD::addButton('top', 'import', 'view', 'import-operation::buttons.import_button');
        });
    }

    /**
     * @return void
     */
    protected function setupImportFileUpload(): void
    {
        $this->crud->hasAccessOrFail('import');

        /**
         * We probably want to extend ImportFileRequest to make it do more of what we want it to do.
         */
        // CRUD::setValidation(ImportFileRequest::class);
        CRUD::setValidation(UploadImportFileRequest::class);

        CRUD::addField([
            'name' => 'file',
            'label' => __('import-operation::import.select_a_file'),
            'type' => 'upload',
            'hint' => __('import-operation::import.accepted_types') . '. ' .
                ($this->example_file_url ? '<a target="_blank" download title="' . __('import-operation::import.download_example') . '" href="' . $this->example_file_url . '">' . __('import-operation::import.download_example') . '</a>' : ''),
        ]);
        
        /**
         * If the user is not a State Agent, add a State selector.
         * If the user is a State Agent, add a State selector but set its value and make it read-only.
         * Either way, we apparently need a State field.
         * We also need a checkbox to indicate if all the state's Mills should be deleted, but we can do that later.
         * Should this be a hidden field for State Agents?
         */
        $user = backpack_user();
        $state = [
            'name' => 'state_id',
            'label' => 'State',
            'type' => 'select',
            'entity' => 'state',
            'model' => 'App\Models\State',
            'attribute' => 'name',
        ];
        /**
         * If the user is a State Agent, we need to make some changes.
         * Rename the select element, disable it, and add a hidden field for state_id instead.
         */
        if ($user->isStateAgent() && !empty($user->state_id)) {
            $state['name'] .= '_display';
            $state['default'] = $user->state_id;
            $state['attributes'] = [
                'disabled' => 'disabled',
            ];

            CRUD::addField([
                'name' => 'state_id',
                'type' => 'hidden',
                'value' => $user->state_id,
            ]);
        }
        CRUD::addField($state);

        /**
         * Add a switch for handling delete all from State
         * Should this only display for state agents?
         * Should this instead appear on the Confirm screen?
         * If next to the state selector we can use JavaScript to keep it updated...
         */
        $stateName = $user?->state?->name ?? 'this state';
        CRUD::addField([
            'name' => 'delete_from_state',
            'type' => 'switch',
            'label' => "Delete existing Mills in $stateName?",
        ]);

    }

    /**
     * Disable the user column mapping step of the import
     * @return void
     */
    // public function disableUserMapping(): void
    // {
    //     CRUD::setOperationSetting("disableUserMapping", true);
    // }

    /**
     * Queue imports to be handled in the background
     * @return void
     */
    // public function queueImport(): void
    // {
    //     CRUD::setOperationSetting('queueImport', true);
    // }

    /**
     * Delete the spreadsheet file after an import is complete
     * @return void
     */
    // public function deleteFileAfterImport(): void
    // {
    //     CRUD::setOperationSetting('deleteFileAfterImport', true);
    // }

    public function previewData(): void
    {
        CRUD::setOperationSetting('previewData', true);
    }

    /**
     * Remove the need for a primary key, only create models
     * @return void
     */
    // public function withoutPrimaryKey(): void
    // {
    //     CRUD::setOperationSetting('disablePrimaryKey', true);
    // }

    /**
     * Set a custom import class, this will skip the mapping phase on the front end
     * @param string $import_class
     * @return void
     */
    // public function setImportHandler(string $import_class): void
    // {
    //     $this->custom_import_handler = $import_class;
    // }

    /**
     * @param string $url
     * @return void
     */
    // public function setExampleFileUrl(string $url): void
    // {
    //     $this->example_file_url = $url;
    // }

    /**
     * @return View
     * Return initial view for import file upload
     */
    public function selectFile(): View
    {
        $this->crud->hasAccessOrFail('import');

        /**
         * FYI, $this->data comes from CrudController
         */
        $this->data['crud'] = $this->crud;
        $this->data['title'] = CRUD::getTitle() ?? __('import-operation::import.import') . ' ' . $this->crud->entity_name_plural;

        $user = backpack_user();
        $this->data['user'] = $user;
        $this->data['userRoles'] = $user->roles()->pluck('name')->toArray();
        $this->data['isStateAgent'] = $user->isStateAgent();

        $this->setupImportFileUpload();

        return view('import-operation::select-file', $this->data);
    }

    /**
     * @return RedirectResponse
     * @throws Exception
     * Handle saving the import file and redirect to field mapper
     */
    public function handleFile(): RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');
        $this->setupImportFileUpload();

        $request = $this->crud->validateRequest();

        /**
         * using config() with ?? instead of using the second parameter for default value seems strange.
         * Another thing I don't like about what happens below is that it defines defaults in multiple places,
         * here and in the config file.
         */
        $disk = config('backpack.operations.import.disk') ?? 'local';
        $path = config('backpack.operations.import.path') ?? 'imports';

        try {
            $file_path = $request->file('file')->store($path, $disk);
        } catch (Exception $e) {
            \Alert::add('error',
                __('import-operation::import.file_upload_problem') . (config('app.env') === 'development') ? $e->getMessage() : ''
            )->flash();
            return redirect()->back();
        }

        /**
         * Snag the original file name and add it to the log.
         */
        $originalFileName = $request->file('file')->getClientOriginalName();
        Log::debug(self::class . '::handleFile(): Uploaded file: ', [
            'file' => $request->file('file'),
            'originalName' => $originalFileName,
        ]);

        $log_model = $this->getImportLogModel();

        /**
         * IMO, the getImportPrimaryKey() method should encrapsulate all the mess below so that we can just do
         *  `$model_primary_key = $this->getImportPrimaryKey();`
         * and $model_primary_key will be null if it should be.
         * 
         */
        $model_primary_key = null;
        if (!($this->crud->getOperationSetting('disablePrimaryKey', 'import') ?? false)) {
            $model_primary_key = $this->getImportPrimaryKey();
        }

        /**
         * Add our additional columns when the log record is created.
         */
        $log = $log_model::create([
            'user_id' => backpack_user()->id,
            'file_path' => $file_path,
            'disk' => $disk,
            'model' => \get_class($this->crud->model),
            'model_primary_key' => $model_primary_key,
            'original_file_name' => $originalFileName,
            'processed_rows' => 0,
            'failed_rows' => 0,
            'total_rows' => 0,
        ]);

        /**
         * We need to cut in around here in a way that let's us use both a custom import handler and a user-defined mapping.
         * Perhaps if the custom_import_handler is defined, we can attempt to use its
         */

        //If a custom import is set, skip directly to handle the import
        // if (!is_null($this->custom_import_handler)) {
        if (null !== $this->custom_import_handler) {
            // Log::debug('We have a custom_import_handler but we\'re still going to map the fields, dammit!', [
            //     'custom_import_handler' => $this->custom_import_handler,
            // ]);
            // if ($this->crud->getOperationSetting('previewData', 'import')) {
            //     return redirect($this->crud->route . '/import/' . $log->id . '/preview');
            // }
            // return $this->handleImport($log->id);
        }

        /**
         * If user mapping is disabled, we skip the map screen.
         * We also skip the map screen if there's a custom import handler.
         * I would have guessed that we skip the mapping when using a custom import handler, but clearly they can work together
         * as long as the custom import handler knows how to map the columns.
         * Here's a/the trap: the visual mapping uses the columns defined in setup AND the spreadsheet column headings.
         * That's a trap because the mapping is invalid if the spreadsheet doesn't have the same columns.
         * To say it another way, you can only select a saved import config if it uses the same column headings as the file that was just uploaded.
         */
        $user_mapping_disabled = $this->crud->getOperationSetting('disableUserMapping', 'import') ?? false;
        if ($user_mapping_disabled) {
            /**
             * Maybe this should go into a method?
             * makeDefaultConfig() or some such?
             * makeFallbackConfig()?
             */
            $config = [];
            foreach ($this->crud->columns() as $column) {
                $config[$column['name']] = [$column];
            }
            $log->config = $config;
            $log->save();

            /**
             * If Preview enabled, go there instead.
             */
            if ($this->crud->getOperationSetting('previewData', 'import')) {
                return redirect($this->crud->route . '/import/' . $log->id . '/preview');
            }

            return $this->handleImport($log->id);
        }

        return redirect($this->crud->route . '/import/' . $log->id . '/map');
    }

    /**
     * @param HeadingRowImport $headingImport
     * @param int $id
     * @return View|RedirectResponse
     * Return view for mapping fields to import columns
     */
    public function mapFields(HeadingRowImport $headingImport, int $id): View|RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');
        //Find the import log
        $log = $this->getCurrentImportLog($id);

        /**
         * If validating the upload fails...
         * validateUploadedFile() would be a better name for the function invoked below.
         */
        if (!$this->validateImport($log)) {
            return redirect($this->crud->route . '/import');
        }

        //Get base level of array if import returns multiple nested arrays for headers
        $column_headers = Excel::toArray($headingImport, $log->file_path, $log->disk);
        do {
            $column_headers = $column_headers[0];
        } while (isset($column_headers[0]) && \is_array($column_headers[0]));

        $required_columns = $this->getRequiredImportColumns();

        // $autoMap = $this->getAutoMap($column_headers);

        return view('import-operation::map-fields', [
            // 'autoMap' => $this->getAutoMap($column_headers),
            'crud' => $this->crud,
            'title' => CRUD::getTitle() ?? __('import-operation::import.import') . ' ' . $this->crud->entity_name_plural,
            'column_headers' => $column_headers,
            'import' => $log,
            'primary_key' => $log->model_primary_key,
            'required_columns' => $required_columns,
        ]);
    }


    /**
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     * Save mapping configuration to import log and redirect to confirmation screen
     */
    public function handleMapping(Request $request, int $id): RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');
        $log = $this->getCurrentImportLog($id);

        $config = [];
        foreach ($this->crud->columns() as $column) {
            // $chosen_heading = $request->get($column['name'] . '__heading');
            $chosen_heading = $request->input($column['name'] . '__heading');
            if ($chosen_heading) {
                if (!isset($config[$chosen_heading])) {
                    $config[$chosen_heading] = [];
                }

                $config[$chosen_heading][] = collect($column)->toArray();
            }
        }

        if (\count($config) === 0) {
            return redirect($this->crud->route . '/import/' . $id . '/map')->withErrors([
                'import' => __('import-operation::import.please_map_at_least_one'),
            ]);
        }

        if (
            //If primary key is not disabled
            !($this->crud->getOperationSetting('disablePrimaryKey', 'import') ?? false) &&
            //And at least one sheet column has not been mapped to the primary key column
            // is_null(collect($config)->filter(function ($items) use ($log) {
            //     return collect($items)->where('name', $log->model_primary_key)->count() > 0;
            // })->first())
            (null === collect($config)->filter(function ($items) use ($log) {
                return collect($items)->where('name', $log->model_primary_key)->count() > 0;
            })->first())
        ) {
            //Redirect back to mapper with error
            return redirect($this->crud->route . '/import/' . $id . '/map')->withErrors([
                'import' => __('import-operation::import.please_map_the_primary_key'),
            ]);
        }

        //Check that all columns with the required rule in the set request are present in mapping
        $required_errors = [];
        $required_columns = $this->getRequiredImportColumns();
        foreach ($required_columns as $required_column) {
            // if (is_null(collect($config)->filter(fn($items) => collect($items)->where('name', $required_column)->count() > 0
            // )->first())) {
            if (null === (collect($config)->filter(fn($items) => collect($items)->where('name', $required_column)->count() > 0
            )->first())) {
                $column_config = collect($this->crud->columns())->where('name', $required_column)->first();
                $column_label = $column_config ? $column_config['label'] : ucfirst(str_replace(' ', '', $required_column));

                $required_errors[] = [$required_column => __('validation.required', [
                    'attribute' => $column_label
                ])];
            }
        }

        if (\count($required_errors) > 0) {
            return redirect($this->crud->route . '/import/' . $id . '/map')->withErrors($required_errors);
        }

        $log->config = $config;
        $log->save();

        return redirect($this->crud->route . '/import/' . $id . '/confirm');
    }

    /**
     * @param int $id
     * @return View|RedirectResponse
     * Show the user their configured import and ask to confirm
     * 
     * Show the field mapping and ask to confirm
     */
    public function confirmImport(int $id): View|RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');

        $log = $this->getCurrentImportLog($id);

        if (!$this->validateImport($log, true)) {
            return redirect($this->crud->route . '/import/' . $id . '/map');
        }

        return view('import-operation::confirm-import', [
            'crud' => $this->crud,
            'title' => CRUD::getTitle() ?? __('import-operation::import.import') . ' ' . $this->crud->entity_name_plural,
            'import' => $log,
        ]);
    }

    /**
     * Check some shit then initiate the import, either immediately or on the queue.
     * 
     * @param int $id
     * @return RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function handleImport(int $id): RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');

        $log = $this->getCurrentImportLog($id);

        /**
         * Jaha!
         * The second parameter indicates whether $log['config'] should be included in the validation.
         * Why would you ever want that?
         * Or why not, for that matter?
         */
        // if (!$this->validateImport($log, is_null($this->custom_import_handler))) {
        // or even null coalescence?
        if (!$this->validateImport($log, null === $this->custom_import_handler)) {
            return redirect($this->crud->route . '/import/' . $id . '/map');
        }

        $formRequest = $this->crud->getFormRequest();

        Log::debug('MillImportOperation::handleImport() formRequest?', ['formRequest' => print_r($formRequest, true)]);

        $file_should_be_deleted = $this->crud->getOperationSetting('deleteFileAfterImport', 'import') ?? false;
        if ($file_should_be_deleted) {
            $log->delete_file_after_import = true;
        }

        $log->started_at = Carbon::now();
        $log->save();

        $import_should_queue = $this->crud->getOperationSetting('queueImport', 'import') ?? false;
        $import_class = $import_should_queue ? QueuedCrudImport::class : CrudImport::class;

        //Set custom import class if it has been specified
        // if (!is_null($this->custom_import_handler)) {
        if (null !== $this->custom_import_handler) {
            $import_class = $this->custom_import_handler;
        }

        if ($import_should_queue) {
            Log::debug(self::class.'::handleImport(): starting queued import...');
            Excel::queueImport(new $import_class($log->id, $formRequest), $log->file_path, $log->disk)->onQueue(config('backpack.operations.import.queue'));
            \Alert::add('success', __('import-operation::import.your_import_has_been_queued'))->flash();
        } else {
            /**
             * Here's where we'll wrap this in a transaction...except I'm pretty sure it's already in a transaction...
             * Except!
             * We can turn off automatic transactions and do it ourselves...
             */
            Log::debug(self::class.'::handleImport(): starting immediate import...', ['importClass' => $import_class]);
            Excel::import(new $import_class($log->id, $formRequest), $log->file_path, $log->disk);
            /**
             * Let's refresh the import log so we can report stats to the user
             */
            $log->refresh();

            // \Alert::add('success', __('import-operation::import.your_import_has_been_processed'))->flash();
            \Alert::add('success', "Import processed. Inserted {$log->processed_rows} out of {$log->total_rows} total rows.")->flash();
        }

        return redirect($this->crud->route);
    }

    /**
     * This is another thing that will break when we start using a custom ImportLog model...
     * 
     * @param int $id
     * @return ImportLog
     */
    // protected function getCurrentImportLog(int $id): ImportLog
    // {
    //     $log_model = $this->getImportLogModel();
    //     $log = $log_model::find($id);
    //     if (!$log) {
    //         abort(404);
    //     }
    //     return $log;
    // }

    /**
     * @return Model|string
     */
    // protected function getImportLogModel(): Model|string
    // {
    //     return config('backpack.operations.import.import_log_model') ?? ImportLog::class;
    // }

    /**
     * @return string
     * @throws Exception
     * Get the model's primary key based on the import config or model setup
     */
    // protected function getImportPrimaryKey(): string
    // {
    //     //First look for a column with primary_key => true
    //     $primary_key_column = collect($this->crud->columns())->where('primary_key', true)->first();
    //     if ($primary_key_column) {
    //         $primary_key = $primary_key_column['name'];
    //     } else {
    //         //Get the current CRUD models' primary key as a fallback if the user has not defined a column as primary key
    //         $model = (new $this->crud->model);
    //         $primary_key = $model->getKeyName();

    //         //Check if a column is defined in import setup
    //         $primary_key_column = collect($this->crud->columns())->where('name', $primary_key)->first();
    //         if (!$primary_key_column) {
    //             //If a column hasn't been set with the model's primary key, choose the first text/number column as a primary key
    //             $first_column = collect($this->crud->columns())->whereIn('type', [
    //                 'text', 'number', TextColumn::class, NumberColumn::class,
    //             ])->first();
    //             if ($first_column) {
    //                 $primary_key = $first_column['name'];
    //             } else {
    //                 throw new PrimaryKeyNotFoundException(\get_class($this->crud->model));
    //             }
    //         }
    //     }
    //     return $primary_key;
    // }

    /**
     * This poorly-named method actually validates the import_log file upload
     * 
     * IMO, this method should be named validateUploadedFile().
     * 
     * @param Model $log
     * @param bool $include_config
     * @return bool
     */
    protected function validateImport(Model $log, bool $include_config = false): bool
    {
        $rules = [
            'file_path' => 'required',
            'model_primary_key' => ($this->crud->getOperationSetting('disablePrimaryKey', 'import') ?? false) ? 'nullable' : 'required',
            'model' => 'required',
        ];
        /**
         * I still haven't figured out why $include_config is an option.
         * Is config maybe in a hidden field on the upload page?
         * If so, that's weird.
         */
        if ($include_config) {
            $rules['config'] = 'required|min:1';
        }
        $import_validator = Validator::make($log->toArray(), $rules);
        return $import_validator->passes();
    }

    /**
     * Inspects the FormRequest class, if there is one, to determine which fields should be marked as required in the mapping.
     * @return array
     */
    protected function getRequiredImportColumns(): array
    {
        $required_columns = [];
        $formRequest = $this->crud->getFormRequest();
        $rules = $formRequest ? (new $formRequest)->rules() : null;
        if ($rules) {
            $required_columns = collect($rules)->filter(function ($rule) {
                return \in_array('required', explode('|', $rule));
            })->keys()->toArray();
        }
        return $required_columns;
    }

    /**
     * I don't remember why I thought I needed this method...
     * Handling automap in the view almost seems better because we're already doing a nested loop to populate the select options.
     * @param array $headings
     * @return array
     */
    protected function getAutoMap(array $headings): array
    {
        /**
         * If we have no columns, what is there to map?
         */
        $columns = $this->crud->columns();
        if (empty($columns) || empty($headings)) {
            return [];
        }

        $cols = collect($columns);
        /**
         * loop over the headings to find the first item that satisfies the conditions
         */
        $autoMap = [];

        foreach ($headings as $heading) {
            $column = $cols->first(fn ($col, $key) => $col['name'] === $heading || $col['key'] === $heading || strtolower($col['label']) === strtolower($heading));
            if ($column) {
                $autoMap[$heading] = $column['name'];
            }
        }

        return $autoMap;
    }

    public function preview(int $id): View|RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');
        $log = $this->getCurrentImportLog($id);

        $this->data['crud'] = $this->crud;
        // $this->data['title'] = ucwords($this->crud->entity_name) . ' Import Preview';
        $this->data['title'] = 'Import Preview :: ' . ucwords($this->crud->entity_name_plural);

        $importer = new MillsCrudImport($log->id);

        /**
         * If there's a user mapping, we need to use bulkMappedRules instead of bulkRules.
         * IIRC, user mapping is indicated by $log->config being populated.
         * 
         */
        if (empty($log->config)) {
            $rules = $importer->rules();
            $bulkRules = $importer->bulkRules();
            $attr = $importer->bulkAttributes();
        } else {
            $rules = $importer->mappedRules();
            $bulkRules = $importer->bulkMappedRules();
            $attr = $importer->bulkMappedAttributes();
        }

        /**
         * Okay!
         * Something I hadn't expected is that the imported data is doubly nested, I guess because of sheets.
         * By doubly nested I mean 
         * [
         *  0 => [
         *      0 => [
         *          ...actual data
         *      ]
         *  ]
         * ]
         * So how do we figure out WTF is going on with it?
         */
        $importData = Excel::toArray($importer, $log->file_path);
        /**
         * I think this will do the trick...
         */
        while (isset($importData[0]) && isset($importData[0][0])) {
            $importData = $importData[0];
        }

        // $validator = Validator::make($importData, $bulkRules, $msgs);
        $validator = Validator::make($importData, $bulkRules, [], $attr);

        if ($validator->fails()) {
            /**
             * I see.
             * It wasn't failing validation after all because the dd() does not execute.
             * Instead, it turns out that the view already had a variable named $errors.
             * We'll name ours errorMessages instead.
             */

            $this->data['errorMessages'] = $this->formatErrors($validator->errors()->toArray());
            $this->data['errorRows'] = array_keys($this->data['errorMessages']);
        }

        /**
         * Actually, we probably want to use the rule keys for columns because that corresponds to the mapped fields
         */
        // $this->data['columns'] = array_keys($importData[0]);
        $this->data['columns'] = array_keys($rules);
        $this->data['columnCount'] = \count($this->data['columns']);
        // $this->data['columnCount'] = \count($bulkRules);
        $this->data['rules'] = $rules;
        $this->data['attributes'] = $attr;
        $this->data['importData'] = $importData;
        $this->data['fieldMap'] = $importer->fieldMap();
        $this->data['import'] = $log;
        $this->data['rowCount'] = \count($importData);

        return view('import-operation::preview', $this->data);
    }

    protected function formatErrors(array $errors): array
    {
        $formattedErrors = [];
        /**
         * Why sort before?
         */
        // ksort($errors);
        foreach ($errors as $rawKey => $messages) {
            [$index, $field] = explode('.', $rawKey, 2);
            $formattedErrors[$index][$field] = $messages[0];
        }
        ksort($formattedErrors, SORT_NUMERIC);
        return $formattedErrors;
    }

    protected function makeConfig(): array
    {
        $config = [];
        foreach ($this->crud->columns() as $column) {
            $config[$column['name']] = [$column];
        }
        return $config;
    }
}
