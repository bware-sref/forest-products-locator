<?php

namespace App\Imports;

use App\Models\Mill;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Override;
use RedSquirrelStudio\LaravelBackpackImportOperation\Interfaces\WithCrudSupport;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;
use Exception;

class FloridaMills implements ToModel, WithHeadingRow, WithCrudSupport
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
        dd($row);
        return new Mill([
            //            
        ]);
    }
}
