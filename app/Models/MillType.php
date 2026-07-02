<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 
 */
class MillType extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\MillTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * add attributes to facilitate use with option values
     */
    protected $appends = ['value', 'label'];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->id,
        );
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->name,
        );
    }


    /**
     * MillType belongs to many Mills
     */
    public function mills(): BelongsToMany
    {
        return $this->belongsToMany(Mill::class);
    }

    public static function rawToIds(string $raw): array
    {
        /**
         * I have mixed feelings about handling the separator this way, we'll see how it goes.
         */
        $raw = Str::lower($raw);
        $separator = Str::contains($raw, '|') ? '|' : ',';
        $types = explode($separator, $raw);
        $ids = MillType::whereIn('name', $types, boolean: 'and', not: false)->pluck('id')->all();
        if (\count($ids) !== \count($types)) {
            Log::warning(self::class.'::rawToIds(): different number of types supplied than ids found.', [
                'ids' => $ids,
                'idCount' => \count($ids),                
                'types' => $types,
                'typeCount' => \count($types),
                'raw' => $raw,
            ]);
        }
        return $ids;
    }
}
