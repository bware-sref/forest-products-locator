/**
 * Holds MillFilters.
 * - Desktop (lg and up): a panel in the page itself, so the map or list stays usable while it's open. The page
 *   decides where it sits through className. Shown/hidden by the Filters button in TitleFilterBar.
 * - Smaller screens: a modal Sheet sliding in from the left.
 */
import { type ReactNode } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

/** The Filters button points at this (aria-controls). */
export const MILL_FILTERS_PANEL_ID = 'mill-filters-panel';
/** The Filters button in TitleFilterBar; focus returns here when the desktop panel is closed from inside. */
export const MILL_FILTERS_TRIGGER_ID = 'filter-trigger';

export interface MillFiltersPanelProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    isDesktop: boolean;
    /** Desktop placement and size; ignored by the mobile Sheet. */
    className?: string;
    children: ReactNode;
}

export function MillFiltersPanel({
    open,
    onOpenChange,
    isDesktop,
    className,
    children,
}: MillFiltersPanelProps) {
    if (!isDesktop) {
        return (
            <Sheet open={open} onOpenChange={onOpenChange}>
                <SheetContent
                    side="left"
                    showCloseButton={false}
                    // sits above the map (z-50) and the title bar
                    overlayClassName="z-200"
                    className="z-200 w-full max-w-screen sm:max-w-screen gap-0 p-0 bg-nature border-r-lorne overflow-y-auto"
                >
                    <SheetHeader className="sr-only">
                        <SheetTitle>Mill Filters</SheetTitle>
                        <SheetDescription>Filter mills based on the criteria below.</SheetDescription>
                    </SheetHeader>
                    <SheetClose className="text-beluga w-full flex flex-row items-end justify-end p-2 -mb-2">
                        <X data-icon="inline-end" size="32" aria-hidden="true" />
                        <span className="sr-only">Close filters</span>
                    </SheetClose>
                    {children}
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <section
            id={MILL_FILTERS_PANEL_ID}
            aria-label="Mill filters"
            hidden={!open}
            // hidden below lg so the server-rendered (desktop) markup never shows on phones before hydration
            className={cn('hidden lg:block relative bg-lorne shadow-xl', className)}
        >
            <button
                type="button"
                id="mill-filters-close"
                onClick={() => {
                    onOpenChange(false);
                    // the panel (and this button) is now hidden, so don't leave focus on it
                    document.getElementById(MILL_FILTERS_TRIGGER_ID)?.focus();
                }}
                // MillFilters' wrapper is z-101, so the button has to sit above it to be clickable
                className="absolute top-1 right-1 z-110 p-1 rounded-sm text-beluga hover:bg-coupe focus-visible:outline-2 focus-visible:outline-beluga"
            >
                <X size="24" aria-hidden="true" />
                <span className="sr-only">Close filters</span>
            </button>
            {children}
        </section>
    );
}
