import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { IFaq, IFaqCategory } from '@/types';

export default function Faqs() {
    const page = usePage<SharedData>();
    const pageTitle = 'FAQs';
    // temporary work-around for unused properties lint
    // page.props.pageTitle = pageTitle;
    const faqsByCategory: IFaqCategory[] = page.props.faqsByCategory as IFaqCategory[];

    console.log(faqsByCategory);

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <h1>{pageTitle}</h1>
                    <ul>
                        {faqsByCategory.map((category) => (
                            <li key={category.slug} >
                                {category.name}
                            </li>
                        ))}
                    </ul>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
