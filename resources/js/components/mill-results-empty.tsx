import {
    type MouseEvent,
    type MouseEventHandler,
} from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * id of the page heading in TitleFilterBar. Focus moves there after Clear filters / Try again,
 * because those buttons disappear once results load, and focus would otherwise drop to the top of the page.
 */
export const MILL_RESULTS_HEADING_ID = 'mill-results-heading';

interface MillResultsEmptyProps {
    /** the last search failed, as opposed to finding nothing */
    loadError?: boolean;
    onClearFiltersClick?: MouseEventHandler<HTMLButtonElement>;
    onRetryClick?: () => void;
    className?: string;
}

/**
 * Shown on the mill list and the mill map when a search finishes with no mills.
 * Screen readers hear the result from the live region in TitleFilterBar, so this isn't a live region itself.
 */
export default function MillResultsEmpty({
    loadError = false,
    onClearFiltersClick,
    onRetryClick,
    className,
}: MillResultsEmptyProps) {

    const focusHeading = () => document.getElementById(MILL_RESULTS_HEADING_ID)?.focus();

    const handleClearClick = (event: MouseEvent<HTMLButtonElement>) => {
        onClearFiltersClick?.(event);
        focusHeading();
    };

    const handleRetryClick = () => {
        onRetryClick?.();
        focusHeading();
    };

    const buttonClassName = "bg-coupe border border-beluga text-beluga text-[16px] font-bold rounded-sm";

    return (
        <div
            className={cn("flex flex-col items-center gap-4 text-center text-beluga", className)}
            data-testid="mill-results-empty"
        >
            {loadError ? (
                <>
                    <p className="text-lg font-bold">We couldn't load the mills.</p>
                    <p>Check your connection, then try again.</p>
                    {onRetryClick && (
                        <Button className={buttonClassName} onClick={handleRetryClick}>
                            Try again
                        </Button>
                    )}
                </>
            ) : (
                <>
                    <p className="text-lg font-bold">No mills match these filters.</p>
                    <p>Try a broader search, a larger radius, or fewer filters.</p>
                    {onClearFiltersClick && (
                        <Button className={buttonClassName} onClick={handleClearClick}>
                            Clear filters
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}
