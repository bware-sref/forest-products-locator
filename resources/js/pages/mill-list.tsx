import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

export default function MillList() {
    const page = usePage<SharedData>();
    const pageTitle = 'Mill List';
    // temporary work-around for unused properties lint
    page.props.pageTitle = pageTitle;

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                        <ul>
                            <li>
                                <h2>Mill Name</h2>
                                <p>Mill Address</p>
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
