import AppLayout from '@/layouts/app-layout';
import DOMPurify from 'isomorphic-dompurify';
import {
    type PageSeoOverride,
    SeoDefaults,
    State,
} from '@/types';
import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import HeroSplit from '@/components/hero-split';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ArrowRight,
    Mail,
    MoveRight,
    Phone,
} from 'lucide-react';
import heroFallback from '@img/pine-trees_short.jpg';
import mobileHeroFallback from '@img/pine-trees_short-390w.jpg';
import mobileHeroFallback2x from '@img/pine-trees_short-780w.jpg';

import { isExternalUrl } from '@/lib/utils';

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
        seo: SeoDefaults;
    }>();

    const siteUrl = page.props.seo.siteUrl;

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
                <HeroSplit
                    src={heroSrc}
                    alt={state.name}
                    sources={heroSources}
                >
                    <div className="flex max-w-83.75 flex-col gap-8 text-white md:max-w-3xl lg:max-w-4xl lg:pt-8">
                        <h1 className="mb-10 w-full text-3xl leading-10 font-bold md:text-[45px]">
                            {statePage?.hero_headline || `${state.name} Forest Products`}
                        </h1>
                        <SafeHtml
                            html={statePage?.hero_copy}
                            className="flex flex-col gap-3 mt-6 pr-5 text-2xl [&_li]:ml-5 [&_ul]:list-disc"
                        />
                    </div>
                </HeroSplit>
            </div>

            {/* Contacts -- always present */}
            {contacts.length > 0 && (
                <section className="w-full bg-white" id="contacts">
                    <div className="mx-auto flex w-full max-w-full flex-col items-center gap-8 px-5 py-10 text-center md:w-6xl lg:w-7xl">
                        <div className="flex flex-col gap-2">
                            <h2 className="text-2xl font-bold md:text-[38px]">
                                {statePage?.contacts_headline
                                    || 'Want more information or a free site suitability analysis?'}
                            </h2>
                            {statePage?.contacts_copy && (
                                <p className="text-[23px]">{statePage.contacts_copy}</p>
                            )}
                        </div>
                        <div className="flex w-full flex-col flex-wrap justify-center gap-5 md:flex-row Xbg-purple-500">
                            {contacts.map(contact => (
                                <Card key={contact.id} className="w-full max-w-full text-left md:max-w-152.25 border-aircraft border-2 bg-white">
                                    <CardContent className="flex flex-col gap-1 text-coupe">
                                        {contact.name && <p className="font-semibold text-[22px]">{contact.name}</p>}
                                        {contact.title && (
                                            <p className="text-sm">{contact.title}</p>
                                        )}
                                        <address className="mt-2 flex flex-col gap-1.5 text-[18px] not-italic">
                                            {contact.address && (
                                                <span className="flex gap-2">
                                                    {/* <MapPin className="mt-0.5 size-4 shrink-0" /> */}
                                                    <span className="whitespace-pre-line">{contact.address}</span>
                                                </span>
                                            )}
                                            {contact.phone && (
                                                <a
                                                    href={`tel:${contact.phone}`}
                                                    className="flex items-center gap-4 hover:underline font-bold"
                                                >
                                                    <Phone className="size-4 shrink-0" />
                                                    {contact.phone}
                                                </a>
                                            )}
                                            {contact.email && (
                                                <a
                                                    href={`mailto:${contact.email}`}
                                                    className="flex items-center gap-4 underline hover:no-underline font-bold"
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
                            <div className="flex flex-1 flex-col gap-4 md:pr-16 self-start md:max-w-162.5">
                                {overview.headline && (
                                    <h2 className="text-2xl font-bold lg:text-[45px]">{overview.headline}</h2>
                                )}
                                <SafeHtml html={overview.body} className="text-[23px]" />
                                {overviewStats.length > 0 && (
                                    <div className="mt-2 grid grid-cols-2 gap-3">
                                        {overviewStats.map(([label, value], i) => (
                                            <div key={i} className="rounded-lg border border-beluga p-3">
                                                <p className="text-xs tracking-wide">{label}</p>
                                                <p className="text-3xl font-bold">{value}</p>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                            {overviewImage && (
                                <img
                                    src={overviewImage}
                                    alt={overview.headline || state.name}
                                    className="max-w-full md:max-w-131.75 flex-1 rounded-lg md:ml-auto"
                                />
                            )}
                        </div>

                        {(forestTypes.length > 0 || forestProducts.length > 0) && (
                            <div className="grid grid-cols-1 gap-8 rounded-xl bg-ebony p-6 lg:grid-cols-2 lg:p-8 border border-white mt-8">
                                {forestTypes.length > 0 && (
                                    <div className="flex flex-col gap-4">
                                        <h3 className="text-xl font-bold md:text-[36px]">Regional Forest Types</h3>
                                        <ul className="flex flex-col gap-4">
                                            {forestTypes.map(type => (
                                                <li key={type.id} className="flex items-start gap-3 bg-baize border border-white px-3 py-1.5 rounded-md">
                                                    {storageUrl(type.icon) && (
                                                        <img
                                                            src={storageUrl(type.icon)}
                                                            alt=""
                                                            aria-hidden="true"
                                                            role="presentation"
                                                            className="mt-1 size-6 shrink-0"
                                                        />
                                                    )}
                                                    <div>
                                                        <p className="text-[20px]">{type.title}</p>
                                                        {type.description && (
                                                            <p className="text-sm">{type.description}</p>
                                                        )}
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                {forestProducts.length > 0 && (
                                    <div className="flex flex-col gap-4 md:pl-6 border-l-2">
                                        <h3 className="text-xl font-bold md:text-[36px]">{state.name} Forest Products</h3>
                                        <div className="flex flex-wrap gap-3">
                                            {forestProducts.map(product => (
                                                <Badge
                                                    key={product.id}
                                                    className="border-white bg-baize px-4 py-1.5 text-[20px] text-white min-w-25"
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
                <section className="w-full bg-background text-black">
                    <div className="mx-auto flex w-full max-w-full flex-col items-center gap-8 px-5 py-10 md:w-6xl lg:w-7xl">
                        {impact.headline && (
                            <h2 className="text-3xl font-bold md:text-[42px]">{impact.headline}</h2>
                        )}
                        <div className="flex w-full flex-col justify-center gap-6 md:flex-row">
                            {impactStats.map(([label, value], i) => (
                                <Card key={i} className="max-w-full flex-1 items-center py-8 md:max-w-[300px] border-coupe border-2">
                                    <CardContent className="w-full">
                                        <p className="text-[42px] font-extrabold">{value}</p>
                                        <p className="mt-2 text-[20px]">{label}</p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* Forestry Agency & Available Assistance -- optional */}
            {agency && (
                <section className="w-full bg-coupe text-white">
                    <div className="mx-auto flex w-full max-w-full flex-col gap-8 px-5 py-10 md:w-6xl lg:w-7xl lg:py-16">
                        <div className="flex flex-col gap-4 bg-aircraft rounded-md px-8 pt-8 pb-12 border-white border">
                            {agency.headline && (
                                <h2 className="text-2xl font-bold lg:text-3xl">{agency.headline}</h2>
                            )}
                            <SafeHtml html={agency.body} className="max-w-3xl md:max-w-full md:pr-3 text-lg mb-4" />
                            <div className="mt-2 flex flex-wrap gap-6">
                                {agency.cta_1_label && agency.cta_1_url && (
                                    <Button
                                        asChild
                                        className="bg-aircraft border-white border rounded-sm hover:bg-white hover:text-aircraft hover:border-aircraft text-lg p-6"
                                    >
                                        <a href={agency.cta_1_url} target="_blank" rel="noreferrer">
                                            {agency.cta_1_label}
                                            <ArrowRight data-icon="inline-end" className="ml-3 size-5" />
                                        </a>
                                    </Button>
                                )}
                                {agency.cta_2_label && agency.cta_2_url && (
                                    <Button
                                        asChild
                                        
                                        className="bg-aircraft border-white border rounded-sm hover:bg-white hover:text-aircraft hover:border-aircraft text-lg p-6"
                                    >
                                        <a href={agency.cta_2_url}>
                                            {agency.cta_2_label}
                                            <ArrowRight data-icon="inline-end" className="ml-3 size-5" />
                                        </a>
                                    </Button>
                                )}
                            </div>
                        </div>

                        {categories.length > 0 && (
                            <div className="flex flex-col gap-4 text-white">
                                {agency.assistance_headline && (
                                    <h3 className="text-4xl font-bold">{agency.assistance_headline}</h3>
                                )}
                                {agency.assistance_copy && <p className="text-xl">{agency.assistance_copy}</p>}
                                <div className="mt-2 grid grid-cols-1 gap-6 md:grid-cols-2">
                                    {categories.map(category => (
                                        <Card key={category.id} className="border-white bg-aircraft border text-white">
                                            <CardHeader>
                                                <CardTitle className="font-bold text-3xl">{category.title}</CardTitle>
                                                {category.description && (
                                                    <p className="text-lg text-white/75">{category.description}</p>
                                                )}
                                            </CardHeader>
                                            {(category.links?.length ?? 0) > 0 && (
                                                <CardContent className="flex flex-col gap-3">
                                                    {category.links!.map(link => (
                                                        <a
                                                            key={link.id}
                                                            href={link.url}
                                                            className="flex items-center justify-between gap-2 border-t border-white/20 hover:underline"
                                                            target={isExternalUrl(link.url, siteUrl) ? '_blank' : '_self'}
                                                        >
                                                            <span>
                                                                <span className="block">{link.label}</span>
                                                                {link.description && (
                                                                    <span className="block text-xs text-white/56">
                                                                        {link.description}
                                                                    </span>
                                                                )}
                                                            </span>
                                                            <MoveRight className="size-6 shrink-0" />
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
