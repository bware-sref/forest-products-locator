<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;

class Import extends ImportLog
{
    /**
     * Yoinked from ImportLog so we can adapt the values for our purposes.
     * @var string
     */
    protected $table = 'imports';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'file_path',
        'original_file_name',
        'disk',
        'model_primary_key',
        'model',
        'config',
        'delete_file_after_import',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'started_at',
        'completed_at'
    ];
    protected $casts = [
        'config' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'delete_file_after_import' => 'boolean',
    ];
}
