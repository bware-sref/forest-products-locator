import {
    type MouseEventHandler,
} from 'react';
import {
    type MillListProps,
} from '@/types';
import {
    ScrollArea,
} from '@/components/ui/scroll-area';
import MillListSkeleton from '@/components/mill-list-skeleton';
import MillListItem from '@/components/mill-list-item';
import MillResultsEmpty from '@/components/mill-results-empty';

interface MillListStatusProps {
    /** a search is in flight (including the first one, before any results) */
    isLoading?: boolean;
    /** the last search failed, as opposed to finding nothing */
    loadError?: boolean;
    onClearFiltersClick?: MouseEventHandler<HTMLButtonElement>;
    onRetryClick?: () => void;
}

/**
 * Placeholders show only while loading. Once a search finishes with no mills,
 * a message replaces the list (an empty list reads as "list, 0 items", which isn't helpful).
 * Screen readers hear the result from the live region in TitleFilterBar, not from here.
 */
export default function MillList({
    mills,
    children,
    isLoading = false,
    loadError = false,
    onClearFiltersClick,
    onRetryClick,
    ...props
}: MillListProps & MillListStatusProps) {

    // 12 skeletons ensures scrollbar immediate present on large screens
    const howManySkeletons = 12;
    const hasMills = mills && mills.length > 0;
    // keep showing the previous results while a new search runs; placeholders only when there's nothing yet
    const showSkeletons = isLoading && !hasMills;

    return (
        <ScrollArea
            className="flex flex-row w-90 max-w-90 md:w-full md:max-w-full mt-6 lg:mt-0 h-200 lg:bg-lorne lg:px-4 lg:py-4 lg:h-screen lg:max-h-full lg:flex-wrap lg:w-full lg:max-w-full"
            {...props}
        >
            {(hasMills || showSkeletons) ? (
                <ul
                    className="flex flex-col md:flex-row md:flex-wrap justify-evenly items-stretch gap-6"
                    aria-busy={isLoading}
                >
                    {hasMills ? mills.map(mill =>
                        <li className="p-0 w-full md:w-75 lg:w-90" key={mill.match_id}>
                            <MillListItem mill={mill} />
                        </li>
                    ) : (
                        // you can't put a for loop inside JSX, so make an array and map over it
                        Array.from({ length: howManySkeletons }).map((_, index) => (
                            <li className="p-0 w-full md:w-75 lg:w-90" key={index}>
                                <MillListSkeleton />
                            </li>
                        ))
                    )}
                    {children}
                </ul>
            ) : (
                <MillResultsEmpty
                    className="py-12"
                    loadError={loadError}
                    onClearFiltersClick={onClearFiltersClick}
                    onRetryClick={onRetryClick}
                />
            )}
        </ScrollArea>
    );
}
