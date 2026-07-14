<?php

namespace App\Models;

use App\Models\Scopes\OrderedScope;
use App\Models\Scopes\PublishedDateScope;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @mixin IdeHelperFaq
 */
#[TypeScript]
#[ScopedBy(PublishedDateScope::class)]
#[ScopedBy(OrderedScope::class)]
class Faq extends Model
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\FaqFactory> */
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'slug',
        'order',
        'published_at',
        'unpublished_at',
        'faq_category_id'
    ];

    public function faqCategory(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class);
    }
}
