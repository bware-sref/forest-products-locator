<?php

namespace App\Models;

use App\Models\Scopes\OrderedScope;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[ScopedBy(OrderedScope::class)]
class FaqCategory extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\FaqCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'order',
    ];

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }
}
