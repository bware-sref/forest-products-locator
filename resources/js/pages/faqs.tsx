import AppLayout from '@/layouts/app-layout';
import { type PageSeoOverride, type IFaqCategory } from '@/types';
import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';

export default function Faqs() {
    const page = usePage<{
        pageSeo: PageSeoOverride;
        faqsByCategory: IFaqCategory[];
    }>();
    const pageTitle = page.props.pageSeo.title;
    const faqsByCategory = page.props.faqsByCategory;

    return (
        <AppLayout>
            <Seo {...page.props.pageSeo} />
            <div className="flex min-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex flex-col w-full lg:max-w-7xl items-center lg:items-start justify-start opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 px-6 gap-5">
                    <h1 className="font-bold text-3xl">{pageTitle}</h1>
                    <ul className="flex flex-col gap-4">
                        {faqsByCategory.map((category) => (
                            <li key={category.slug}
                                className="border-b last:border-0"
                            >
                                <h2 className="font-bold text-xl mb-4">
                                    {category.name}
                                </h2>
                                <ul className="flex flex-col gap-4">
                                    {category.faqs.map((faq) => (
                                        <li key={faq.id}
                                            className="odd:bg-lorne even:bg-coupe p-4"
                                        >
                                            <div className="question mb-2">{faq.question}</div>
                                            <div className="answer">{faq.answer}</div>
                                        </li>
                                    ))}
                                </ul>
                            </li>
                        ))}
                    </ul>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
