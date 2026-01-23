import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { BasicMap } from '@/components/basic-map';

export default function MillMap() {
    const page = usePage<SharedData>();
    const pageTitle = "Mill Map";
    // temporary work-around for unused properties lint
    page.props.pageTitle = pageTitle;

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                        <BasicMap />
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
