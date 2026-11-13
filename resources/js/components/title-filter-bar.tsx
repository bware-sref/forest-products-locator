/**
 * Title and Filter Bar for Mill Map and Mill List pages
 * They both use this with minor differences (text, mostly)
 */
import {
    MouseEventHandler,
} from "react";
import {
    Button
} from "@/components/ui/button";
import {
    DownloadIcon,
    SlidersHorizontalIcon,
    X,
} from "lucide-react";
import { Spinner } from '@/components/ui/spinner';
import { MILL_RESULTS_HEADING_ID } from '@/components/mill-results-empty';
import { MILL_FILTERS_PANEL_ID, MILL_FILTERS_TRIGGER_ID } from '@/components/mill-filters-panel';
import { millCountText, millStatusMessage } from '@/lib/mill-status';
import { type ActiveFilter, type ActiveFilterKey } from '@/hooks/use-mills';

const FILTER_TRIGGER_ID = MILL_FILTERS_TRIGGER_ID;

export interface TitleFilterBarProps {
    headline: string;
    isDownloading: boolean;
    isLoading: boolean;
    millCount?: number;
    /** the last mill search failed (as opposed to finding nothing) */
    loadError?: boolean;
    handleExportClick: MouseEventHandler<HTMLButtonElement>;
    handleClickCapture?: MouseEventHandler<HTMLDivElement> | undefined;

    // --- Filters panel (see useFilterPanel) ---
    filtersOpen: boolean;
    onFiltersToggle: () => void;
    isDesktop: boolean;
    /** Shown as removable chips on desktop, and as a count on the Filters button on smaller screens. */
    activeFilters: ActiveFilter[];
    onRemoveFilter: (key: ActiveFilterKey) => void;
}

export function TitleFilterBar({
    headline,
    isDownloading,
    isLoading,
    millCount,
    loadError = false,
    handleExportClick,
    handleClickCapture,
    filtersOpen,
    onFiltersToggle,
    isDesktop,
    activeFilters,
    onRemoveFilter,
    ...props
}: TitleFilterBarProps) {

    const statusMessage = millStatusMessage({ isLoading, loadError, millCount });
    const activeCount = activeFilters.length;

    /**
     * The removed chip disappears, so move focus to the next chip (or the previous one, if it was last), or to the
     * Filters button once none are left. Otherwise keyboard and screen reader users are dropped at the top of the page.
     */
    const removeFilter = (index: number) => {
        const nextKey = activeFilters[index + 1]?.key ?? activeFilters[index - 1]?.key;
        onRemoveFilter(activeFilters[index].key);
        requestAnimationFrame(() => {
            const next = nextKey ? document.getElementById(`active-filter-${nextKey}`) : null;
            (next ?? document.getElementById(FILTER_TRIGGER_ID))?.focus();
        });
    };

    return (
        <>
        {/**
         * Note: comments cannot occur prior to the opening tag of the return value.
         * 
         * Full-width wrapper for title bar
         */}
        <div data-thing="title-bar" 
            className="flex flex-col items-center px-4 lg:px-8 text-velvet lg:justify-center bg-lorne border-b-6"
            onClickCapture={handleClickCapture}
            {...props}
        >
            {/** title bar + filter controls */}
            <div className="w-full lg:max-w-7xl mx-auto flex flex-row items-center justify-between pl-2 md:px-0 2xl:px-6 py-2">
                <div data-thing="" className="flex flex-row gap-x-5 items-center">
                    {/* tabIndex -1: focus lands here after Clear filters / Try again (see MillResultsEmpty) */}
                    <h1
                        id={MILL_RESULTS_HEADING_ID}
                        tabIndex={-1}
                        className="font-bold text-3xl text-beluga focus:outline-none"
                    >
                        {headline}
                    </h1>
                    {/* keeps the previous count on screen during a new search; shows 0 once loaded.
                        aria-hidden because the live region below says the same thing. */}
                    {!loadError && ((millCount ?? 0) > 0 || !isLoading) ? (
                        <span className="text-beluga" aria-hidden="true">
                            {millCountText(millCount)}
                        </span>
                    ) : ''}
                    {/* always rendered: a live region added along with its text often isn't announced.
                        Hidden from screen readers while the mobile filters Sheet is open; MillFilters announces then. */}
                    <span role="status" className="sr-only">{statusMessage}</span>
                    {isLoading || isDownloading ? (
                        <Spinner data-icon="inline-end" className="ml-auto size-8 text-beluga" />
                    ) : ''}
                </div>
                <div data-thing="button-wrap" className="flex flex-row gap-5">
                    <Button
                        className="bg-coupe border border-beluga text-beluga text-[16px] font-bold rounded-sm z-20"
                        id="export-trigger"
                        onClick={handleExportClick}
                        disabled={isDownloading || isLoading}
                    >
                        <span className="sr-only lg:not-sr-only">Export</span>
                        <DownloadIcon
                            data-icon="inline-end"                            
                            className="w-6 h-6 size-1"
                        />
                    </Button>

                    <Button
                        className="relative bg-coupe border border-beluga text-beluga text-[16px] font-bold justify-self-end rounded-sm z-20"
                        id={FILTER_TRIGGER_ID}
                        onClick={onFiltersToggle}
                        aria-expanded={filtersOpen}
                        // the desktop panel is in the page; the mobile Sheet manages its own relationship
                        aria-controls={isDesktop ? MILL_FILTERS_PANEL_ID : undefined}
                    >
                        <span className="sr-only lg:not-sr-only">Filters</span>
                        {activeCount > 0 && (
                            <span className="sr-only">, {activeCount} active</span>
                        )}
                        <SlidersHorizontalIcon
                            data-icon="inline-end"
                            className="w-6 h-6 lg:ml-2 size-1"
                        />
                        {/* smaller screens have no room for chips, so show how many filters are on */}
                        {activeCount > 0 && (
                            <span
                                aria-hidden="true"
                                className="lg:hidden absolute -top-2 -right-2 min-w-5 h-5 px-1 rounded-full bg-beluga text-velvet text-xs font-bold leading-5 text-center"
                            >
                                {activeCount}
                            </span>
                        )}
                    </Button>
                </div>
            </div>

            {/* desktop: active filters as removable chips, so they stay visible with the panel closed */}
            {activeCount > 0 && (
                <ul
                    aria-label="Active filters"
                    className="hidden lg:flex w-full lg:max-w-7xl mx-auto flex-row flex-wrap gap-2 pb-3 md:px-0 2xl:px-6"
                >
                    {activeFilters.map((filter, index) => (
                        <li key={filter.key}>
                            <button
                                type="button"
                                id={`active-filter-${filter.key}`}
                                onClick={() => removeFilter(index)}
                                className="inline-flex items-center gap-1 rounded-full border border-beluga bg-coupe px-3 py-1 text-sm text-beluga hover:bg-beluga hover:text-velvet focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-beluga"
                            >
                                {filter.label}
                                <X aria-hidden="true" className="size-4" />
                                <span className="sr-only">, remove filter</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
        </>
    );
}