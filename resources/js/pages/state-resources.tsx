import AppLayout from '@/layouts/app-layout';
import {
    State,
} from '@/types';
import {
    Head,
    Link,
    usePage,
} from '@inertiajs/react';
import Hero from '@/components/hero';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { CircleArrowRight } from 'lucide-react';
import heroFallback from '@img/pine-trees_short.jpg';
import mobileHero from '@img/pine-trees_short-390w.jpg';
import mobileHero2x from '@img/pine-trees_short-780w.jpg';
// import stateResource from '@/routes/state-resource';

const heroSources = [
    {
        srcSet: mobileHero,
        media: '(max-width: 390px)',
    },
    {
        srcSet: mobileHero2x + ' 2x',
        media: '(max-width: 768px)',
    },
];

/**
 * This page sort of calls out the fact that we don't have anything defined
 * for each State.
 * @returns 
 */
export default function StateResources() {
    const page = usePage<{
        states: State[];
        pageTitle?: string;
    }>();

    const states = page.props.states;

    return (
        <AppLayout>
            <Head title={page.props.pageTitle} />
            {/** 
             * Hero must be in a full-width wrapper.
            */}
            <Hero
                src={heroFallback}
                alt="Pine trees"
                pictureClassName={'col-start-1 row-start-1 h-full w-full max-w-full object-cover'}
                sources={heroSources}
            >
                <div className="flex flex-col gap-12 lg:gap-8 max-w[335px] lg:max-w-3xl justify-self-center items-center-safe text-white Xbg-red-500/30">
                    <h1 className="text-3xl lg:text-5xl leading-10 font-bold mt-8 mb-6 w-full">
                        {page.props.pageTitle}
                    </h1>
                    <p className="text-[18px] lg:text-[22px] leading-8 my-5">
                        The Primary Forest Products Locator is a tool provided by the <em className="italic">Southern Group of State Foresters</em> to assist buyers in locating primary wood product manufacturing companies.
                    </p>
                </div>
            </Hero>

            {/**
             * Cards!
             */}
            <div className="cards mx-auto mt-4 py-6 px-5 flex flex-col flex-wrap md:flex-row w-full md:w-6xl lg:w-7xl max-w-full xl:max-w-7xl items-stretch justify-between gap-8 lg:gap-6 Xbg-pink-400">
                {states.map( state => 
                <Card key={state.id} className="w-full md:w-55 lg:w-70 lg:max-w-70 xl:w-87.5 xl:max-w-95 pt-0 border-0 rounded-2xl bg-coupe">
                    <CardHeader className="bg-coupe py-4 xl:pt-6 xl:pb-3 rounded-t-2xl Xbg-amber-500">
                        <CardTitle className="text-beluga">
                            <Link
                                href={'/state-resources/' + state.abbreviation}
                                className="text-beluga flex justify-between text-[27px]"
                            >
                                <span className="underline inline hover:no-underline">{state.name}</span>
                                <CircleArrowRight 
                                    data-icon="inline-end"
                                    size={40}
                                    className="size-10"
                                />
                            </Link>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="mr-5 bg-coupe text-beluga text-xl">
                        {state.resource_summary}
                    </CardContent>
                </Card>                    
                )}
            </div>
        </AppLayout>
    );
}
