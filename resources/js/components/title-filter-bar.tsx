/**
 * Title and Filter Bar for Mill Map and Mill List pages
 * They both use this with minor differences (text, mostly)
 */
import {
    MouseEventHandler,
    ReactNode,
    useState
} from "react";
import {
    Button
} from "@/components/ui/button";
import {
    DialogDrawer
} from "@/components/extend/dialog-drawer";
import {
    DialogProps
} from "vaul";
import {
    DownloadIcon,
    SlidersHorizontalIcon
} from "lucide-react";
import { Spinner } from '@/components/ui/spinner';
import { MILL_RESULTS_HEADING_ID } from '@/components/mill-results-empty';
import { millCountText, millStatusMessage } from '@/lib/mill-status';

export interface TitleFilterBarProps {
    headline: string;
    children: ReactNode;
    isDownloading: boolean;
    isLoading: boolean;
    millCount?: number;
    /** the last mill search failed (as opposed to finding nothing) */
    loadError?: boolean;
    handleExportClick: MouseEventHandler<HTMLButtonElement>;
    handleClickCapture?: MouseEventHandler<HTMLDivElement> | undefined;
}

export function TitleFilterBar({
    headline,
    children,
    isDownloading,
    isLoading,
    millCount,
    loadError = false,
    handleExportClick,
    handleClickCapture,
    ...props
}: TitleFilterBarProps) {

    const [drawerOpen, setDrawerOpen] = useState<boolean>(false);

    const statusMessage = millStatusMessage({ isLoading, loadError, millCount });

    /**
     * make triggerButton a component
     */
    const triggerButton = (
        <Button
            className="bg-coupe border border-beluga text-beluga text-[16px] font-bold justify-self-end rounded-sm z-20"
            id="filter-trigger"
        >
            <span className="sr-only lg:not-sr-only"><span className="sr-only">Toggle </span>Filters</span>
            <SlidersHorizontalIcon
                data-icon="inline-end"                            
                className="w-6 h-6 lg:ml-2 size-1"
            />
        </Button>
    );

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
                        Hidden from screen readers while Filters is open; MillFilters has its own then. */}
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

                    <DialogDrawer
                        trigger={triggerButton}
                        title="Mill Filters"
                        description="Filter mills based on the criteria below."
                        drawerContentProps={{
                            className: "bg-transparent z-200 border-r-lorne w-full max-w-screen p-0 ",                            
                        }}
                        drawerHeaderProps={{
                            className: "sr-only"
                        }}
                        drawerProps={{
                            direction: "left",
                            modal: true,
                            onOpenChange: setDrawerOpen,
                            autoFocus: drawerOpen,
                            className: 'w-screen min-w-full max-w-full'
                        } as DialogProps}
                        dialogHeaderProps={{
                            className: "sr-only"
                        }}
                        dialogContentProps={{
                            className: "bg-nature lg:bg-lorne z-100 border-lorne",                            
                        }}
                        dialogProps={{
                            modal: true,
                            onOpenChange: setDrawerOpen,
                            autoFocus: drawerOpen
                        } as DialogProps}
                    >
                        {/**
                         * Expect MillFilters as children
                         */}
                        {children}
                    </DialogDrawer>
                </div>
            </div>
        </div>
        </>
    );
}