import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import Hero from '@/components/hero';
import { Button } from '@/components/ui/button';
import { ArrowRightIcon } from 'lucide-react';
import heroImage from '@img/lumber-flipped@2x.jpg';
import heroFallback from '@img/lumber-flipped.jpg';
import { millMap, millList, stateResources, addBusiness } from '@/routes';

export default function Welcome() {
    const page = usePage<SharedData>();
    const pageTitle = 'Welcome';
    // temporary work-around for unused properties lint
    page.props.pageTitle = pageTitle;
    page.props.contentClassName = 'max-w-screen';
    return (
        <AppLayout>
            <Head title={pageTitle} />
            {/*
            <div className="flex min-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col lg:max-w-4xl Xlg:flex-row justify-items-start">
                    <div className="flex w-full items-center justify-center flex-col">
                    <div className="hero-content absolute inset-0 flex items-start max-w-[335px] lg:max-w-4xl">
                    */}
                        <div className="grid grid-cols-1 grid-rows-1 w-full">
                            {/* Hero is a wrapper for <picture> */}
                            <Hero
                                src={heroFallback}
                                alt="Lumber"
                                pictureClassName={'col-start-1 row-start-1 h-full w-full object-cover'} />

                            {/** 
                             * content of hero 
                             * need full width wrapper to overlay the image
                             * inside of full-width, define a content column that is only as wide as top-nav
                             */}
                            <div className="hero-content w-full col-start-1 row-start-1 flex flex-col Xbg-sky-500/30">
                                {/**
                                 * below is our content column matching the size of the top nav with mx-auto to center it
                                 * however, we may need yet another wrapper to constrain width of the children
                                 */}
                                <div className="hero-content__inner mx-auto flex flex-col gap-8 h-full w-full lg:w-7xl max-w-[335px] lg:max-w-7xl items-start p-5 Xbg-amber-300/30">
                                    <div className="flex flex-col gap-8 max-w[335px] lg:max-w-3xl justify-self-center items-center-safe Xbg-red-500/30">
                                        <h1 className="text-5xl font-bold my-6">
                                            Welcome to the Primary Forest Products Locator
                                        </h1>
                                        <p className="text-[22px] my-5">
                                            The Primary Forest Products Locator is a tool provided by the <em className="italic">Southern Group of State Foresters</em> to assist buyers in locating primary wood product manufacturing companies.
                                        </p>
                                        <Button
                                            asChild
                                            size="lg"
                                            
                                            className="grow-0 place-self-start bg-coupe border-white border-2 text-2xl py-6"
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
                                </div>
                            </div>
                        </div>
                        <div className="mx-auto border-1 mt-4 cards flex w-full lg:w-7xl max-w-[335px] lg:max-w-7xl items-stretch justify-between">
                            <div className="card">card</div>
                            <div className="card">card</div>
                            <div className="card">card</div>
                        </div>
            {/*                        
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
            */}
        </AppLayout>
    );
}
