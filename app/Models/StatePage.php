<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperStatePage
 */
class StatePage extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\StatePageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'state_id',
        'hero_headline',
        'hero_img_dt',
        'hero_img_mobile',
        'hero_copy',
        'contacts_headline',
        'contacts_copy',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
