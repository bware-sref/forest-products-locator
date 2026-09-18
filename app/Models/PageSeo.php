<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\PageSeoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPageSeo
 */
class PageSeo extends Model
{
    use CrudTrait;

    /** @use HasFactory<PageSeoFactory> */
    use HasFactory;

    protected $fillable = [
        /**
         * FFS, this should have been relative path or URL instead of route_name because route_name is not unique.
         * E.g., "mills.show" is the route name for every individual mill page, but "/mill-list/a-a-mills" is unique.
         * Also, I just noticed that MillController::index() uses the incorrect route name "mill-list" instead of 
         * "mills.index"
         * In any case, the solution for state pages is perhaps to add SEO fields to one of the StatePage models, e.g., StatePage.
         */
        'route_name',
        'title',
        'description',
        'og_image',
    ];

    /**
     * Resolve SEO metadata for a static page, layering an admin-managed
     * override (if one exists for $routeName) on top of the page's own
     * code-defined defaults, on top of the site-wide default description.
     *
     * @return array{title: string, description: string, image: ?string}
     */
    public static function resolve(string $routeName, string $defaultTitle, ?string $defaultDescription = null): array
    {
        $record = static::query()->where('route_name', $routeName)->first();

        return [
            'title' => $record?->title ?: $defaultTitle,
            'description' => $record?->description ?: $defaultDescription ?: config('seo.default_description'),
            'image' => $record?->og_image ?: null,
        ];
    }
}
