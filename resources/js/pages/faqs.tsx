import AppLayout from '@/layouts/app-layout';
import { type PageSeoOverride, type IFaqCategory } from '@/types';
import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import {
    SafeHtml
} from "@/components/safe-html"
import { FaqCategoryNav } from '@/components/faq-category-nav';

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

                    {/**
                     * nav first in the DOM (and on the left on desktop) so reading/tab order matches
                     * visual order. Its stickiness lives in the component: a sticky side list on
                     * desktop, a sticky "Jump to" bar on smaller screens.
                     */}
                    <div className="grid w-full gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
                        <FaqCategoryNav categories={faqsByCategory} />

                        <ul className="flex flex-col gap-4">
                            {faqsByCategory.map((category) => (
                                <li key={category.slug}
                                    className="border-b last:border-0 pb-4"
                                >
                                    {/* scroll margin clears the sticky mobile bar (up to ~6rem when the category name wraps at 320px) / sits just below the top on desktop */}
                                    <h2
                                        id={category.slug}
                                        className="font-bold text-2xl mb-4 scroll-mt-28 lg:scroll-mt-6"
                                    >
                                        {category.name}
                                    </h2>
                                    <ul className="flex flex-col gap-4">
                                        {category.faqs.map((faq) => (
                                            <li key={faq.id}
                                                className="odd:bg-lorne even:bg-coupe p-4"
                                            >
                                                <div className="question flex flex-row gap-4 mb-2 text-lg">
                                                    <div className="text-xl font-bold">
                                                        Q<span className="sr-only">uestion</span>:
                                                    </div>
                                                    {faq.question}
                                                </div>
                                                <div className="answer flex flex-row gap-4 text-lg pr-4">
                                                    <div className="text-xl font-bold">
                                                        A<span className="sr-only">nswer</span>:
                                                    </div>
                                                    <SafeHtml
                                                        html={faq.answer}
                                                        className="answerd"
                                                    />
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* mobile: the sticky bar covers jumping between categories, but not getting back to the page top. "#top" scrolls to the top of the document natively. */}
                    <a
                        href="#top"
                        data-smooth-scroll
                        className="self-center underline underline-offset-4 hover:decoration-2 lg:hidden"
                    >
                        Return to top
                    </a>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
