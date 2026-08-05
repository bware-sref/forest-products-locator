import AppLayout from '@/layouts/app-layout';
import DOMPurify from 'isomorphic-dompurify';
import {
    type PageSeoOverride,
    State,
} from '@/types';
import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import Hero from '@/components/hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ArrowUpRight,
    CircleArrowRight,
    Mail,
    MapPin,
    Phone,
} from 'lucide-react';
import heroFallback from '@img/pine-trees_short.jpg';
import mobileHeroFallback from '@img/pine-trees_short-390w.jpg';
import mobileHeroFallback2x from '@img/pine-trees_short-780w.jpg';

/** Backpack's upload field stores a path relative to the public disk. */
function storageUrl(path?: string | null): string | undefined {
    if (!path) return undefined;
    return path.startsWith('http') || path.startsWith('/') ? path : `/storage/${path}`;
}

/** CKEditor fields store HTML; sanitize before rendering. */
function SafeHtml({ html, className }: { html?: string; className?: string }) {
    if (!html) return null;
    return (
        <div
            className={className}
            dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(html) }}
        />
    );
}

export default function StatePage() {
    const page = usePage<{
        state: State;
        pageTitle?: string;
        pageSeo: PageSeoOverride;
    }>();

    const state = page.props.state;
    const statePage = state.state_page;
    const contacts = state.state_contacts ?? [];
    const overview = state.state_forest_overview;
    const forestTypes = state.state_forest_types ?? [];
    const forestProducts = state.state_forest_products ?? [];
    const impact = state.state_economic_impact;
    const agency = state.state_forestry_agency;
    const categories = agency?.assistance_categories ?? [];

    const overviewStats = overview
        ? ([
              [overview.stat_1_label, overview.stat_1_value],
              [overview.stat_2_label, overview.stat_2_value],
              [overview.stat_3_label, overview.stat_3_value],
              [overview.stat_4_label, overview.stat_4_value],
          ] as const).filter(([label, value]) => label && value)
        : [];

    const impactStats = impact
        ? ([
              [impact.stat_1_label, impact.stat_1_value],
              [impact.stat_2_label, impact.stat_2_value],
              [impact.stat_3_label, impact.stat_3_value],
          ] as const).filter(([label, value]) => label && value)
        : [];

    // hero_img_dt/hero_img_mobile mirror <Hero>'s src/sources art-direction
    // shape directly; fall back to the site's bundled forest photo until a
    // state has its own uploaded image.
    const heroSrc = storageUrl(statePage?.hero_img_dt) ?? heroFallback;
    const heroSources = statePage?.hero_img_mobile
        ? [
              { srcSet: storageUrl(statePage.hero_img_mobile), media: '(max-width: 768px)' },
          ]
        : [
              { srcSet: mobileHeroFallback, media: '(max-width: 390px)' },
              { srcSet: mobileHeroFallback2x + ' 2x', media: '(max-width: 768px)' },
          ];

    const overviewImage = storageUrl(overview?.image);

    return (
        <AppLayout>
            <Seo {...page.props.pageSeo} />

            {/* Hero -- always present */}
            <div className="w-full bg-petroleum">
                <Hero
                    src={heroSrc}
                    alt={state.name}
                    pictureClassName="col-start-1 row-start-1 h-full w-full max-w-full object-cover opacity-40"
                    sources={heroSources}
                >
                    <div className="flex max-w-83.75 flex-col gap-6 text-beluga lg:max-w-3xl">
                        <h1 className="mt-8 mb-6 w-full text-3xl leading-10 font-bold lg:text-5xl">
                            {statePage?.hero_headline || `${state.name} Forest Products`}
                        </h1>
                        <SafeHtml
                            html={statePage?.hero_copy}
                            className="flex flex-col gap-3 text-lg [&_li]:ml-5 [&_ul]:list-disc"
                        />
                    </div>
                </Hero>
            </div>

            {/* Contacts -- always present */}
            {contacts.length > 0 && (
                <section className="w-full bg-background">
                    <div className="mx-auto flex w-full max-w-full flex-col items-center gap-8 px-5 py-10 text-center md:w-6xl lg:w-7xl">
                        <div className="flex max-w-3xl flex-col gap-2">
                            <h2 className="text-2xl font-bold lg:text-3xl">
                                {statePage?.contacts_headline
                                    || 'Want more information or a free site suitability analysis?'}
                            </h2>
                            {statePage?.contacts_copy && (
                                <p className="text-lg">{statePage.contacts_copy}</p>
                            )}
                        </div>
                        <div className="flex w-full flex-col flex-wrap justify-center gap-6 md:flex-row">
                            {contacts.map(contact => (
                                <Card key={contact.id} className="w-full max-w-full text-left md:w-80">
                                    <CardContent className="flex flex-col gap-1">
                                        {contact.name && <p className="font-semibold">{contact.name}</p>}
                                        {contact.title && (
                                            <p className="text-sm text-muted-foreground">{contact.title}</p>
                                        )}
                                        <address className="mt-2 flex flex-col gap-1.5 text-sm not-italic">
                                            {contact.address && (
                                                <span className="flex gap-2">
                                                    <MapPin className="mt-0.5 size-4 shrink-0" />
                                                    <span className="whitespace-pre-line">{contact.address}</span>
                                                </span>
                                            )}
                                            {contact.phone && (
                                                <a
                                                    href={`tel:${contact.phone}`}
                                                    className="flex items-center gap-2 hover:underline"
                                                >
                                                    <Phone className="size-4 shrink-0" />
                                                    {contact.phone}
                                                </a>
                                            )}
                                            {contact.email && (
                                                <a
                                                    href={`mailto:${contact.email}`}
                                                    className="flex items-center gap-2 hover:underline"
                                                >
                                                    <Mail className="size-4 shrink-0" />
                                                    {contact.email}
                                                </a>
                                            )}
                                        </address>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* Forest Overview -- optional */}
            {overview && (
                <section className="w-full bg-nature text-beluga">
                    <div className="mx-auto flex w-full max-w-full flex-col gap-8 px-5 py-10 md:w-6xl lg:w-7xl lg:py-16">
                        <div className="flex flex-col items-center gap-8 lg:flex-row">
                            <div className="flex flex-1 flex-col gap-4">
                                {overview.headline && (
                                    <h2 className="text-2xl font-bold lg:text-3xl">{overview.headline}</h2>
                                )}
                                <SafeHtml html={overview.body} className="text-lg" />
                                {overviewStats.length > 0 && (
                                    <div className="mt-2 grid grid-cols-2 gap-3">
                                        {overviewStats.map(([label, value], i) => (
                                            <div key={i} className="rounded-lg border border-beluga/40 p-3">
                                                <p className="text-xs tracking-wide uppercase opacity-80">{label}</p>
                                                <p className="text-2xl font-bold">{value}</p>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                            {overviewImage && (
                                <img
                                    src={overviewImage}
                                    alt={overview.headline || state.name}
                                    className="max-w-full flex-1 rounded-lg"
                                />
                            )}
                        </div>

                        {(forestTypes.length > 0 || forestProducts.length > 0) && (
                            <div className="grid grid-cols-1 gap-8 rounded-xl bg-ebony p-6 lg:grid-cols-2 lg:p-8">
                                {forestTypes.length > 0 && (
                                    <div className="flex flex-col gap-4">
                                        <h3 className="text-xl font-bold">Regional Forest Types</h3>
                                        <ul className="flex flex-col gap-4">
                                            {forestTypes.map(type => (
                                                <li key={type.id}>
                                                    <p className="font-semibold">{type.title}</p>
                                                    {type.description && (
                                                        <p className="text-sm opacity-90">{type.description}</p>
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                {forestProducts.length > 0 && (
                                    <div className="flex flex-col gap-4">
                                        <h3 className="text-xl font-bold">{state.name} Forest Products</h3>
                                        <div className="flex flex-wrap gap-2">
                                            {forestProducts.map(product => (
                                                <Badge
                                                    key={product.id}
                                                    className="border-transparent bg-baize px-3 py-1.5 text-sm text-beluga"
                                                >
                                                    {product.label}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </section>
            )}

            {/* Economic Impact -- optional */}
            {impact && impactStats.length > 0 && (
                <section className="w-full bg-background">
                    <div className="mx-auto flex w-full max-w-full flex-col items-center gap-8 px-5 py-10 text-center md:w-6xl lg:w-7xl">
                        {impact.headline && (
                            <h2 className="text-2xl font-bold lg:text-3xl">{impact.headline}</h2>
                        )}
                        <div className="flex w-full flex-col justify-center gap-6 md:flex-row">
                            {impactStats.map(([label, value], i) => (
                                <Card key={i} className="max-w-full flex-1 items-center py-8 text-center md:max-w-70">
                                    <CardContent>
                                        <p className="text-3xl font-bold">{value}</p>
                                        <p className="mt-2 text-sm text-muted-foreground">{label}</p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* Forestry Agency & Available Assistance -- optional */}
            {agency && (
                <section className="w-full bg-coupe text-beluga">
                    <div className="mx-auto flex w-full max-w-full flex-col gap-8 px-5 py-10 md:w-6xl lg:w-7xl lg:py-16">
                        <div className="flex flex-col gap-4">
                            {agency.headline && (
                                <h2 className="text-2xl font-bold lg:text-3xl">{agency.headline}</h2>
                            )}
                            <SafeHtml html={agency.body} className="max-w-3xl text-lg" />
                            <div className="mt-2 flex flex-wrap gap-4">
                                {agency.cta_1_label && agency.cta_1_url && (
                                    <Button asChild variant="secondary">
                                        <a href={agency.cta_1_url} target="_blank" rel="noreferrer">
                                            {agency.cta_1_label}
                                            <ArrowUpRight />
                                        </a>
                                    </Button>
                                )}
                                {agency.cta_2_label && agency.cta_2_url && (
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="border-beluga text-beluga hover:bg-beluga/10 hover:text-beluga"
                                    >
                                        <a href={agency.cta_2_url}>
                                            {agency.cta_2_label}
                                            <CircleArrowRight />
                                        </a>
                                    </Button>
                                )}
                            </div>
                        </div>

                        {categories.length > 0 && (
                            <div className="flex flex-col gap-4">
                                {agency.assistance_headline && (
                                    <h3 className="text-xl font-bold">{agency.assistance_headline}</h3>
                                )}
                                {agency.assistance_copy && <p>{agency.assistance_copy}</p>}
                                <div className="mt-2 grid grid-cols-1 gap-6 md:grid-cols-2">
                                    {categories.map(category => (
                                        <Card key={category.id} className="border-transparent bg-aircraft text-beluga">
                                            <CardHeader>
                                                <CardTitle className="text-lg">{category.title}</CardTitle>
                                                {category.description && (
                                                    <p className="text-sm opacity-90">{category.description}</p>
                                                )}
                                            </CardHeader>
                                            {(category.links?.length ?? 0) > 0 && (
                                                <CardContent className="flex flex-col gap-3">
                                                    {category.links!.map(link => (
                                                        <a
                                                            key={link.id}
                                                            href={link.url}
                                                            className="flex items-center justify-between gap-2 border-t border-beluga/20 pt-3 first:border-t-0 first:pt-0 hover:underline"
                                                        >
                                                            <span>
                                                                <span className="block">{link.label}</span>
                                                                {link.description && (
                                                                    <span className="block text-xs opacity-80">
                                                                        {link.description}
                                                                    </span>
                                                                )}
                                                            </span>
                                                            <ArrowUpRight className="size-4 shrink-0" />
                                                        </a>
                                                    ))}
                                                </CardContent>
                                            )}
                                        </Card>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </section>
            )}
        </AppLayout>
    );
}
