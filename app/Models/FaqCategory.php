<?php

namespace App\Models;

use App\Models\Scopes\OrderedScope;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Faq> $faqs
 * @property-read int|null $faqs_count
 * @method static \Database\Factories\FaqCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
