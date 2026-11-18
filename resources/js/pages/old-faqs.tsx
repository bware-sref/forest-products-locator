import AppLayout from '@/layouts/app-layout';
import {
    type PageSeoOverride,
    type IFaq,
} from '@/types';
import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import {
    SafeHtml
} from "@/components/safe-html"
import { FaqSearch } from '@/components/faq-search';
import { faqElementId } from '@/lib/faqs';

export default function Faqs() {
    const page = usePage<{
        pageSeo: PageSeoOverride;
        faqs: IFaq[];
    }>();
    const pageTitle = page.props.pageSeo.title;
    const faqs = page.props.faqs;

    return (
        <AppLayout>
            <Seo {...page.props.pageSeo} />
            <div className="flex min-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex flex-col w-full lg:max-w-7xl items-center lg:items-start justify-start opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 px-6 gap-5">
                    <h1 className="font-bold text-3xl">{pageTitle}</h1>

                    <FaqSearch faqs={faqs} />

                    {/**
                     * nav first in the DOM (and on the left on desktop) so reading/tab order matches
                     * visual order. Its stickiness lives in the component: a sticky side list on
                     * desktop, a sticky "Jump to" bar on smaller screens.
                     */}
                    <div className="grid w-full gap-8 Xlg:grid-cols-[16rem_minmax(0,1fr)]">
                        {/* <FaqCategoryNav categories={faqsByCategory} /> */}

                        <ul className="flex flex-col gap-4">
                            {faqs.map((faq) => (
                                <li key={faq.id}
                                    // the search jumps here; tabIndex lets it take focus, and the scroll margins match the category headings'
                                    id={faqElementId(faq.id)}
                                    tabIndex={-1}
                                    className="faq-item odd:bg-lorne even:bg-coupe p-4 scroll-mt-28 lg:scroll-mt-6 focus:outline-2 focus:outline-offset-2 focus:outline-beluga group"
                                >
                                    <div className="question flex flex-row gap-4 mb-2 text-2xl Xitems-center">
                                        <div className="text-3xl font-bold">
                                            Q<span className="sr-only">uestion</span>:
                                        </div>
                                        <div>
                                            {faq.question}
                                        </div>                                        
                                    </div>
                                    <div className="answer flex flex-row gap-4 text-xl pr-4">
                                        <div className="text-2xl font-bold">
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
