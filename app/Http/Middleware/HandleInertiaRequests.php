<?php

namespace App\Http\Middleware;

use App\Models\MillType;
use App\Models\WoodSpecies;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),

            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'csrf_token' => csrf_token(),
            'env' => config('app.env'),

            'seo' => [
                'description' => config('seo.default_description'),
                'ogType' => config('seo.default_og_type'),
                'twitterHandle' => config('seo.twitter_handle'),
                // Origin only (no trailing slash) -- combined with Inertia's
                // own page.url (path, query stripped) client-side to build
                // canonical/og:url. Sourced from config, not the request, so
                // it's consistent between SSR and CSR and can't be spoofed
                // via a forged Host header.
                'siteUrl' => rtrim(config('app.url'), '/'),
            ],
        ];
    }

    public function shareOnce(Request $request): array
    {
        return array_merge(parent::shareOnce($request), [
            'millTypes' => fn () => MillType::get(['id', 'name'])->toArray(),
            'woodSpecies' => fn () => WoodSpecies::get(['id', 'name'])->toArray(),
        ]);
    }
}
