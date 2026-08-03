import { Head, usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import defaultOgImage from '@img/forest-products-locator_logo@2x.png';

interface SeoProps {
    /**
     * Passed straight through to Inertia's <Head title>, which already
     * appends " - {app name}" globally (see resources/js/app.tsx /
     * ssr.tsx). Don't add the suffix here too.
     */
    title: string;
    description?: string;
    /** Absolute URL. Falls back to the site logo if omitted/null. */
    image?: string | null;
    /** e.g. "article" for blog-style pages. Defaults to "website". */
    type?: string;
    /**
     * Absolute URL. Defaults to the current page's URL (siteUrl + Inertia's
     * page.url, query string stripped -- per Open Graph/Meta guidance, the
     * canonical/og:url shouldn't include a query string unless it changes
     * the page's actual content, which none of ours do). Override only for
     * pages that need a different canonical target (e.g. pagination).
     */
    canonical?: string;
}

/**
 * head-key on every tag matches app.blade.php's fallback markup
 * (title/description use Inertia's own "inertia"/"data-inertia" keying;
 * everything else here is unique to this component) so nested <Seo>
 * usage -- or a future layout-level default -- dedupes correctly instead
 * of emitting duplicate tags.
 */
export function Seo({ title, description, image, type, canonical }: SeoProps) {
    const page = usePage<SharedData>();
    const { name: siteName, seo } = page.props;

    const resolvedDescription = description ?? seo.description;
    const resolvedImage = image ?? defaultOgImage;
    const resolvedType = type ?? seo.ogType;
    // og:title/twitter:title aren't covered by Inertia's title callback,
    // so the suffix has to be added explicitly here to match what the
    // actual <title> tag will show.
    const fullTitle = `${title} - ${siteName}`;
    const currentPath = page.url.split('?')[0];
    const resolvedCanonical = canonical ?? `${seo.siteUrl}${currentPath}`;

    return (
        <Head title={title}>
            <meta head-key="description" name="description" content={resolvedDescription} />

            <meta head-key="og:title" property="og:title" content={fullTitle} />
            <meta head-key="og:description" property="og:description" content={resolvedDescription} />
            <meta head-key="og:image" property="og:image" content={resolvedImage} />
            <meta head-key="og:type" property="og:type" content={resolvedType} />
            <meta head-key="og:site_name" property="og:site_name" content={siteName} />
            <meta head-key="og:url" property="og:url" content={resolvedCanonical} />

            <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
            <meta head-key="twitter:title" name="twitter:title" content={fullTitle} />
            <meta head-key="twitter:description" name="twitter:description" content={resolvedDescription} />
            <meta head-key="twitter:image" name="twitter:image" content={resolvedImage} />
            <meta head-key="twitter:url" name="twitter:url" content={resolvedCanonical} />
            {seo.twitterHandle && (
                <meta head-key="twitter:site" name="twitter:site" content={seo.twitterHandle} />
            )}

            <link head-key="canonical" rel="canonical" href={resolvedCanonical} />
        </Head>
    );
}
