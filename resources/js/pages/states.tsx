import AppLayout from '@/layouts/app-layout';
import {
    type PageSeoOverride,
    State,
} from '@/types';
import {
    Link,
    usePage,
} from '@inertiajs/react';
import { Seo } from '@/components/seo';
import Hero from '@/components/hero';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { CircleArrowRight } from 'lucide-react';
import heroFallback from '@img/pine-trees_short.jpg';
import mobileHero from '@img/pine-trees_short-390w.jpg';
import mobileHero2x from '@img/pine-trees_short-780w.jpg';

const heroSources = [
    {
        srcSet: heroFallback,
        media: '(min-width: 769px)',
    },
    {
        srcSet: mobileHero2x + ' 2x',
        media: '(min-width: 391px)',
    },
];


export default function States() {
    const page = usePage<{
        appName: string;
        states: State[];
        pageTitle?: string;
        pageSeo: PageSeoOverride;
    }>();

    const states = page.props.states;
    const appName = page.props.appName;

    return (
        <AppLayout>
            <Seo {...page.props.pageSeo} />

            {/** 
             * Hero must be in a full-width wrapper.
            */}
            <Hero
                src={mobileHero}
                alt="Pine trees"
                sources={heroSources}
            >
                <div className="flex flex-col gap-12 lg:gap-8 lg:max-w-3xl justify-self-center items-center-safe text-white my-11 md:pb-10">
                    <h1 className="text-4xl lg:text-5xl leading-10 font-bold X-mt-8 X-mb-6 w-full">
                        {page.props.pageTitle}
                    </h1>
                    <p className="text-[20px] lg:text-[22px] leading-8 mt-6">
                        The <span className="app-name whitespace-nowrap font-bold">{appName}</span> is a tool provided by the <em className="italic">Southern Group of State Foresters</em> to assist buyers in locating primary wood product manufacturing companies.
                    </p>
                </div>
            </Hero>

            <div className="cards mx-auto mt-4 flex w-full max-w-full flex-col flex-wrap items-stretch justify-evenly gap-8 px-5 py-6 md:w-6xl md:flex-row lg:w-7xl lg:gap-6">
                {states.map(state => (
                    <Card
                        key={state.id}
                        className="w-full border-0 rounded-2xl bg-coupe pt-0 md:w-55 lg:w-70 lg:max-w-70 xl:w-87.5 xl:max-w-95"
                    >
                        <CardHeader className="rounded-t-2xl bg-coupe py-4 xl:pt-6 xl:pb-3">
                            <CardTitle className="text-beluga">
                                <Link
                                    href={'/states/' + state.slug}
                                    className="flex justify-between text-[27px] text-beluga"
                                >
                                    <span className="inline underline hover:no-underline">{state.name}</span>
                                    <CircleArrowRight
                                        data-icon="inline-end"
                                        size={40}
                                        className="size-10"
                                    />
                                </Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="mr-5 bg-coupe text-xl text-beluga">
                            {state.resource_summary}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AppLayout>
    );
}
