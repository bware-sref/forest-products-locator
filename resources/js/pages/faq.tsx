import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

export default function Faq() {
    const page = usePage<SharedData>();
    const pageTitle = 'FAQ';
    // temporary work-around for unused properties lint
    page.props.pageTitle = pageTitle;

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                        <h1>{pageTitle}</h1>
                        <ul>
                            <li>
                                <h2>State Your Business Name</h2>
                                <p>State Your Business Address</p>
                                <p>Map This Location</p>
                                <p><strong>Species:</strong> Hardwood &amp; Softwood</p>
                                <p><strong>Mill Type:</strong> Sawmill</p>
                            </li>
                        </ul>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
