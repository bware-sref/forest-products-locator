import AppLayout from '@/layouts/app-layout';
// import { type SharedData } from '@/types';
// import { Head, Link, usePage } from '@inertiajs/react';
import { Head, Link } from '@inertiajs/react';
import Hero from '@/components/hero';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { ArrowRightIcon, CircleArrowRight } from 'lucide-react';
// import heroImage from '@img/lumber-flipped@2x.jpg';
import heroFallback from '@img/lumber-flipped.jpg';
import mobileHero from '@img/lumber-flipped-390w.jpg';
import mobileHero2x from '@img/lumber-flipped-780w.jpg';
import { millMap, millList, stateResources, addBusiness } from '@/routes';

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

export default function Welcome() {
    // const page = usePage<SharedData>();
    const pageTitle = 'Welcome';
    // temporary work-around for unused properties lint
    // page.props.pageTitle = pageTitle;
    // page.props.contentClassName = 'max-w-screen';
    const cards = [
        {
            title: 'Mill List',
            href: millList(),
            content: 'View and search our directory of lumber mills providing primary forest products.',
            key: 'millList',
        },
        {
            title: 'State Resources',
            href: stateResources(),
            content: 'Many additional resources are available to buyers. Browse these resources by state and region.',
            key: 'stateResources',
        },
        {
            title: 'Add Your Business',
            href: addBusiness(),
            content: 'Reach a global audience of potential customers by adding your lumber business to our directory.',
            key: 'addBusiness',
        },
    ];

    return (
        <AppLayout>
            <Head title={pageTitle}>
                <meta name="description" content="Welcome to Fantasy Isle!" />
            </Head>
            {/** 
             * Hero must be in a full-width wrapper.
            */}
            <Hero
                src={heroFallback}
                alt="Lumber"
                pictureClassName={'col-start-1 row-start-1 h-full w-full max-w-full object-cover'}
                sources={heroSources}
            >
                <div className="flex flex-col gap-12 lg:gap-8 max-w[335px] lg:max-w-3xl justify-self-center items-center-safe text-white Xbg-red-500/30">
                    <h1 className="text-3xl lg:text-5xl leading-10 font-bold my-11 lg:my-6">
                        Welcome to the Primary Forest Products Locator
                    </h1>
                    <p className="text-[18px] lg:text-[22px] leading-8 my-10 lg:my-5">
                        The Primary Forest Products Locator is a tool provided by the <em className="italic">Southern Group of State Foresters</em> to assist buyers in locating primary wood product manufacturing companies.
                    </p>
                    <Button
                        asChild
                        size="lg"                        
                        className="grow-0 place-self-center lg:place-self-start bg-coupe border-white border-2 text-xl lg:text-2xl py-10"
                    >
                        <Link
                            href={millMap()}
                            className="grow-0"
                        >
                            View Mill Map
                            <ArrowRightIcon 
                                data-icon="inline-end"
                                size={40}
                                className="w-8 h-8 size-8 ml-2"
                            />
                        </Link>
                    </Button>                                
                </div>
            </Hero>

            {/**
             * Cards!
             */}
            <div className="cards mx-auto mt-4 py-6 px-5 flex flex-col md:flex-row w-full md:w-6xl lg:w-7xl max-w-full xl:max-w-7xl items-stretch justify-between gap-8 lg:gap-6 Xbg-pink-400">
                {cards.map( card =>
                <Card key={card.key} className="w-full md:w-55 lg:w-70 lg:max-w-70 xl:w-87.5 xl:max-w-95 pt-0 border-0 rounded-2xl">
                    <CardHeader className="bg-coupe py-4 xl:py-6 rounded-t-2xl">
                        <CardTitle className="text-beluga">
                            <Link
                                href={card.href}
                                className="text-beluga flex justify-between text-[27px]"
                            >
                                <span className="underline inline hover:no-underline">{card.title}</span>
                                <CircleArrowRight 
                                    data-icon="inline-end"
                                    size={40}
                                    className="size-10"
                                />
                            </Link>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="mr-5 text-black text-xl">
                        {card.content}
                    </CardContent>
                </Card>
                )}
            </div>

        </AppLayout>
    );
}
