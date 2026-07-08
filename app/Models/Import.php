<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $file_path
 * @property string $original_file_name
 * @property string $disk
 * @property string|null $model_primary_key
 * @property string $model
 * @property array<array-key, mixed>|null $config
 * @property bool $delete_file_after_import
 * @property int $total_rows
 * @property int $imported_rows importing happens before processing
 * @property int $processed_rows
 * @property int $failed_rows
 * @property int|null $state_id The state in which the mills in this import are located.
 * @property bool $delete_from_state Should any existing mills in this state be deleted?
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int $duration
 * @property-read string|null $file_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mill> $mills
 * @property-read int|null $mills_count
 * @property-read \App\Models\State|null $state
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereDeleteFileAfterImport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereDeleteFromState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereFailedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereImportedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereModelPrimaryKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereOriginalFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereProcessedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereTotalRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereUserId($value)
 * @mixin \Eloquent
 * @mixin IdeHelperImport
 */
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
        'state_id',
        'delete_from_state',
        'started_at',
        'completed_at'
    ];
    protected $casts = [
        'config' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'delete_file_after_import' => 'boolean',
        'delete_from_state' => 'boolean',
    ];

    /**
     * User relationship defined on the parent class.
     */

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Import hasMany Mills
     * However, we don't want to accidentally fetch mills whenever we fetch an import
     */
    public function mills(): HasMany
    {
        return $this->hasMany(Mill::class);
    }
}
