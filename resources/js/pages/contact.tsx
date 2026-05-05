import AppLayout from '@/layouts/app-layout';
// import { type SharedData } from '@/types';
// import { Head, usePage } from '@inertiajs/react';

import { Head } from '@inertiajs/react';
import { ContactForm } from '@/components/contact-form';

export default function Contact() {
    // const page = usePage<SharedData>();
    const pageTitle = 'Contact';
    // temporary work-around for unused properties lint
    // page.props.pageTitle = pageTitle;

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-180 flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex flex-col w-full items-start justify-start opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 bg-zucchini lg:max-w-7xl py-8 px-8">                    
                    <h1 className="text-3xl font-bold">{pageTitle}</h1>
                    <ContactForm />
                </div>
            </div>
        </AppLayout>
    );
}
