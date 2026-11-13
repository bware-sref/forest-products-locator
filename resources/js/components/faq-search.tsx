import { useMemo, useState } from 'react';
import { Autocomplete } from '@base-ui/react/autocomplete';
import { SearchIcon } from 'lucide-react';
import { type IFaqCategory } from '@/types';
import { faqElementId } from '@/lib/faqs';

interface SearchableFaq {
    id: number;
    question: string;
    category: string;
    /** Question and answer as plain text, for matching. */
    text: string;
}

/** Answers are HTML from the admin's editor. Rough is fine: this is only matched against, never shown. */
function toPlainText(html: string): string {
    return html
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .replace(/&quot;/g, '"')
        .replace(/&#0?39;|&apos;/g, "'")
        .replace(/&rsquo;/g, '’')
        .replace(/\s+/g, ' ');
}

/**
 * Search box for the FAQ page: lists the FAQs whose question or answer contains every word typed, and jumps to the
 * one picked (scrolling to it and moving keyboard focus there, so reading continues from the answer).
 * Built on Base UI's Autocomplete, which handles the combobox keyboard and screen reader behavior.
 */
export function FaqSearch({ categories }: { categories: IFaqCategory[] }) {
    const [query, setQuery] = useState('');
    // case- and accent-insensitive
    const { contains } = Autocomplete.useFilter({ sensitivity: 'base' });

    const items = useMemo<SearchableFaq[]>(
        () =>
            categories.flatMap((category) =>
                category.faqs.map((faq) => ({
                    id: faq.id,
                    question: faq.question,
                    category: category.name,
                    text: `${faq.question} ${toPlainText(faq.answer)}`,
                })),
            ),
        [categories],
    );

    const words = query.trim().split(/\s+/).filter(Boolean);
    const matchCount = words.length === 0 ? 0 : items.filter((item) => words.every((word) => contains(item.text, word))).length;

    const goTo = (item: SearchableFaq) => {
        // after the popup closes, which puts focus back in the search box
        requestAnimationFrame(() => {
            const target = document.getElementById(faqElementId(item.id));
            if (!target) {
                return;
            }
            // scrolls too (smoothly, per app.css); scroll-margin keeps it below the sticky category bar
            target.focus();
        });
    };

    return (
        <Autocomplete.Root
            items={items}
            value={query}
            onValueChange={(value, details) => {
                // keep what was typed rather than replacing it with the chosen question, so another match is a
                // click away
                if (details.reason !== 'item-press') {
                    setQuery(value);
                }
            }}
            filter={(item, search) => {
                const searchWords = search.trim().split(/\s+/).filter(Boolean);
                return searchWords.length > 0 && searchWords.every((word) => contains(item.text, word));
            }}
            itemToStringValue={(item) => item.question}
        >
            <div className="flex w-full max-w-xl flex-col gap-1">
                <label htmlFor="faq-search" className="font-bold">
                    Search the FAQs
                </label>
                <div className="relative">
                    <SearchIcon aria-hidden="true" className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-velvet" />
                    <Autocomplete.Input
                        id="faq-search"
                        placeholder="e.g., location, listing, export"
                        className="h-10 w-full rounded-sm border border-beluga bg-beluga pr-3 pl-9 text-base text-velvet placeholder:text-velvet/80 focus:outline-2 focus:outline-offset-2 focus:outline-beluga"
                    />
                </div>
            </div>

            <Autocomplete.Portal hidden={words.length === 0}>
                <Autocomplete.Positioner className="z-50 outline-hidden" sideOffset={4}>
                    <Autocomplete.Popup className="w-(--anchor-width) max-w-(--available-width) rounded-sm border border-beluga bg-lorne text-beluga shadow-xl">
                        <Autocomplete.Status className="sr-only">
                            {words.length > 0 && `${matchCount} ${matchCount === 1 ? 'question' : 'questions'} found.`}
                        </Autocomplete.Status>
                        <Autocomplete.Empty>
                            <p className="px-3 py-3 text-base">No questions match “{query.trim()}”.</p>
                        </Autocomplete.Empty>
                        <Autocomplete.List className="max-h-[min(24rem,var(--available-height))] overflow-y-auto overscroll-contain py-1 empty:p-0">
                            {(item: SearchableFaq) => (
                                <Autocomplete.Item
                                    key={item.id}
                                    value={item}
                                    onClick={() => goTo(item)}
                                    className="flex cursor-pointer flex-col gap-0.5 px-3 py-2 outline-hidden select-none data-highlighted:bg-beluga data-highlighted:text-velvet"
                                >
                                    <span className="font-bold">{item.question}</span>
                                    <span className="text-sm">{item.category}</span>
                                </Autocomplete.Item>
                            )}
                        </Autocomplete.List>
                    </Autocomplete.Popup>
                </Autocomplete.Positioner>
            </Autocomplete.Portal>
        </Autocomplete.Root>
    );
}
