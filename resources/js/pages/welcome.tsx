import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import Hero from '@/components/hero';
// import { BasicMap } from '@/components/basic-map';
import heroImage from '@img/lumber-flipped@2x.jpg';
import heroFallback from '@img/lumber-flipped.jpg';

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
                    */}
                        <div className="flex w-full items-center justify-center flex-col">
                            <Hero src={heroFallback} alt="Lumber" />
                            <div className="hero-content absolute max-w-[335px] lg:max-w-4xl">
                                <h1 className="text-5xl">Welcome to the Primary Forest Products Locator</h1>
                                <p className="text-[22px] p-8 mb-5">The Primary Forest Products Locator is a tool provided by the <em className="italic">Southern Group of State Foresters</em> to assist buyers in locating primary wood product manufacturing companies.</p>
                                <p>button-style link</p>
                            </div>
                        </div>
                        <div className="mx-auto border-1 mt-4 cards flex w-full max-w-[335px] lg:max-w-4xl items-stretch justify-between">
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
