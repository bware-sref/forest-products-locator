import { useEffect, useRef, useState } from 'react';
import { type IFaqCategory } from '@/types';
import { cn } from '@/lib/utils';
import { useActiveSection } from '@/hooks/use-active-section';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDownIcon } from 'lucide-react';

const NAV_ID = 'faq-category-nav';

type Category = Pick<IFaqCategory, 'slug' | 'name'>;

interface FaqCategoryNavProps {
    categories: Category[];
    className?: string;
}

/**
 * The category links themselves, shared by the desktop list and the mobile panel.
 * Plain <a href="#slug"> rather than Inertia's <Link> on purpose -- the browser handles the
 * scroll (smooth via app.css), the URL hash, and moving the keyboard focus starting point to the heading.
 * The active state isn't color-only: bold + a left bar (WCAG 1.4.1).
 */
function CategoryLinks({
    categories,
    activeSlug,
    onNavigate,
}: {
    categories: Category[];
    activeSlug?: string;
    onNavigate?: () => void;
}) {
    return (
        <ul className="flex flex-col gap-1">
            {categories.map((category) => {
                const isActive = category.slug === activeSlug;
                return (
                    <li key={category.slug}>
                        <a
                            href={`#${category.slug}`}
                            aria-current={isActive ? 'true' : undefined}
                            onClick={onNavigate}
                            className={cn(
                                'block border-l-4 border-transparent py-1 pl-3 underline underline-offset-4 hover:decoration-2 focus-visible:decoration-2',
                                isActive && 'border-zucchini font-bold'
                            )}
                        >
                            {category.name}
                        </a>
                    </li>
                );
            })}
        </ul>
    );
}

/**
 * "Table of categories" for the FAQ page, highlighting the category currently being read.
 *
 * - lg and up: a sticky list of the category links.
 * - below lg: a sticky bar showing the current category; it opens a panel with the same links.
 *   A disclosure rather than a native <select>: desktop browsers draw a select's options as a
 *   popup as wide as the longest option, which overflows narrow viewports, and the select itself
 *   had to truncate the long category names. Here both the bar and the links wrap.
 *
 * Only one of the two is displayed at a time, so the parent lays this out as a single grid cell.
 * Category headings need matching scroll margins (see faqs.tsx) so they land below the sticky bar.
 */
export function FaqCategoryNav({ categories, className }: FaqCategoryNavProps) {
    // threshold sits just below where headings land (scroll-mt-28 = 112px on mobile, 24px on desktop)
    const activeSlug = useActiveSection(categories.map((category) => category.slug), 150);
    const activeName = categories.find((category) => category.slug === activeSlug)?.name;
    const [open, setOpen] = useState(false);
    const mobileNavRef = useRef<HTMLElement>(null);
    const triggerRef = useRef<HTMLButtonElement>(null);

    // close the mobile panel on Escape (returning focus to the trigger) or a tap/click outside it
    useEffect(() => {
        if (!open) {
            return;
        }
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
                triggerRef.current?.focus();
            }
        };
        const onPointerDown = (event: PointerEvent) => {
            if (!mobileNavRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('pointerdown', onPointerDown);
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('pointerdown', onPointerDown);
        };
    }, [open]);

    return (
        <>
            {/* desktop: sticky list */}
            <nav
                id={NAV_ID}
                aria-labelledby={`${NAV_ID}-heading`}
                data-smooth-scroll
                className={cn(
                    'hidden rounded-md bg-coupe p-4 lg:sticky lg:top-6 lg:block lg:max-h-[calc(100vh-3rem)] lg:self-start lg:overflow-y-auto',
                    className
                )}
            >
                <h2 id={`${NAV_ID}-heading`} className="mb-3 text-xl font-bold">
                    Categories
                </h2>
                <CategoryLinks categories={categories} activeSlug={activeSlug} />
            </nav>

            {/* mobile: sticky bar + disclosure panel */}
            <nav
                ref={mobileNavRef}
                aria-label="FAQ categories"
                data-smooth-scroll
                className={cn('sticky top-0 z-20 -mx-2 lg:hidden', className)}
            >
                <Collapsible open={open} onOpenChange={setOpen}>
                    <CollapsibleTrigger
                        ref={triggerRef}
                        className={cn(
                            'flex w-full items-center gap-3 bg-coupe px-4 py-3 text-left shadow-md',
                            open ? 'rounded-none' : 'rounded-b-md'
                        )}
                    >
                        <span className="min-w-0 flex-1">
                            <span className="block text-sm">Category</span>
                            <span className="block font-bold">{activeName}</span>
                        </span>
                        <ChevronDownIcon
                            aria-hidden="true"
                            className={cn('size-5 shrink-0 transition-transform motion-reduce:transition-none', open && 'rotate-180')}
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent className="absolute inset-x-0 top-full max-h-[70vh] overflow-y-auto rounded-b-md border-t border-beluga/40 bg-coupe px-2 py-3 shadow-md">
                        <CategoryLinks
                            categories={categories}
                            activeSlug={activeSlug}
                            onNavigate={() => setOpen(false)}
                        />
                    </CollapsibleContent>
                </Collapsible>
            </nav>
        </>
    );
}
