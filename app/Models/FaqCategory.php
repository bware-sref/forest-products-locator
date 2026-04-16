<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\FaqCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
    ];

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }
}
