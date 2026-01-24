import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
// import { BasicMap } from '@/components/basic-map';


export default function Welcome() {
    const page = usePage<SharedData>();
    const pageTitle = 'Welcome';
    // temporary work-around for unused properties lint
    page.props.pageTitle = pageTitle;

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col lg:max-w-4xl Xlg:flex-row justify-items-start">
                        <div className="flex w-full items-center justify-center flex-col">
                            <p className="p-8 border-1 mb-5">Hero image</p>
                            <p>Hero text</p>
                        </div>
                        <div className="border-1 mt-4 cards flex w-full items-stretch justify-between">
                            <div className="card">card</div>
                            <div className="card">card</div>
                            <div className="card">card</div>
                        </div>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
