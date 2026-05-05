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
 * @property int $id
 * @property string $question
 * @property string $answer
 * @property string $slug
 * @property int $order
 * @property int|null $faq_category_id
 * @property string $published_at
 * @property string|null $unpublished_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FaqCategory|null $faqCategory
 * @method static \Database\Factories\FaqFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereFaqCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereUnpublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereUpdatedAt($value)
 * @mixin \Eloquent
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
