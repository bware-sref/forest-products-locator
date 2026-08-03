import AppLayout from '@/layouts/app-layout';
import { type PageSeoOverride } from '@/types';
import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { ContactForm } from '@/components/contact-form';

export default function Contact() {
    const { pageSeo } = usePage<{ pageSeo: PageSeoOverride }>().props;
    const pageTitle = pageSeo.title;

    return (
        <AppLayout>
            <Seo {...pageSeo} />
            <div className="flex min-h-180 flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex flex-col w-full items-start justify-start opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 lg:max-w-lg lg:py-3 px-8">                    
                    <h1 className="text-3xl font-bold mb-3">{pageTitle}</h1>
                    <ContactForm />
                </div>
            </div>
        </AppLayout>
    );
}
