import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { IFaqCategory } from '@/types';

export default function Faqs() {
    const page = usePage<SharedData>();
    const pageTitle = 'FAQs';
    const faqsByCategory: IFaqCategory[] = page.props.faqsByCategory as IFaqCategory[];

    // console.log(faqsByCategory);

    return (
        <AppLayout>
            <Head title={pageTitle} />
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
