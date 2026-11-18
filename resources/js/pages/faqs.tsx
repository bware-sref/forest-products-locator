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
import {
  Discordion as Accordion,
  DiscordionContent as AccordionContent,
  DiscordionItem as AccordionItem,
  DiscordionTrigger as AccordionTrigger,
} from "@/components/extend/discordion"
import {
    ChevronDownIcon
} from "lucide-react";
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

                    <Accordion
                        type="single"
                        defaultValue={faqs[0].id.toString()}
                        className="w-full"
                    >
                        {faqs.map((faq) => (
                            <AccordionItem
                                key={faq.id}
                                value={faq.id.toString()}
                                className="odd:bg-lorne even:bg-coupe py-4 px-8"
                            >
                                <AccordionTrigger
                                    asHeading="h2"
                                    className="text-2xl lg:text-3xl text-beluga"
                                    id={faqElementId(faq.id)}
                                    icon={
                                        <ChevronDownIcon className="pointer-events-none size-4 shrink-0 translate-y-0.5 text-beluga transition-transform duration-200" />
                                    }
                                >
                                    {faq.question}
                                </AccordionTrigger>
                                <AccordionContent
                                    className="answer text-xl lg:text-2xl py-4 px-6 lg:py-6 lg:px-10 Xlg:max-w-4/5 bg-beluga text-velvet"
                                >
                                    <SafeHtml
                                        html={faq.answer}
                                        className="answerd"
                                    />
                                </AccordionContent>
                            </AccordionItem>
                        ))}
                    </Accordion>



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
