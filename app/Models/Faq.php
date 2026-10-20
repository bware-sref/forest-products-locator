<?php

namespace App\Models;

use App\Models\Scopes\OrderedScope;
use App\Models\Scopes\PublishedDateScope;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
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

    protected static function booted(): void
    {

        static::creating(function (Faq $faq) {
            /**
             * Weird.
             * In QA, published_at being null is no problem.
             * In local, published_at being null causes an exception.
             * DB schema has default of CURRENT_TIMESTAMP but apparently Laravel passes null which conflicts with CURRENT_TIMESTAMP.
             * Should we just update the migration to allow null?
             * If we don't allow null and always publish on save, then I'm not sure there's a point to having
             * published_at in the first place.
             * On the other hand, even if we don't allow null and use CURRENT_TIMESTAMP as the default, that's effectively
             * the same thing as always publishing immediately (if it actually worked).
             * WRT UI, perhaps a checkbox to indicate that it should be immediately published?
             * Also, maybe we should put this on creating instead?
             * Otherwise this will happen every time we save.
             */
            if (null === $faq->published_at) {
                $faq->published_at = now();
            }
        });

        static::saving(function (Faq $faq) {
            /**
             * I think we might just want to always update the slugs.
             */
            $faq->slug = Str::slug($faq->question);
        });
    }

    public function faqCategory(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class);
    }
}
