import {
    useState
} from "react";
import AppLayout from '@/layouts/app-layout';
import {
    type MillType,
    type State,
    type WoodSpecies,
    // type SearchParams,
} from '@/types';
import {
    Head,
    usePage,
} from '@inertiajs/react';
import { useMills } from '@/hooks/use-mills';
import MillFilters from '@/components/mill-filters';
import MillList from '@/components/mill-list';
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


export default function MillListPage() {
    const page = usePage<{
        states: State[];
        millTypes?: MillType[];
        woodSpecies?: WoodSpecies[];
        pageTitle?: string;
        millsApiUrl: string;
        csrf_token: string;
    }>();

    const {
        mills,
        states,
        counties,
        searchText,
        filterResetKey,
        isLoading,
        isDownloading,
        handleTextSearchChange,
        handleStateSelectChange,
        handleCountySelectChange,
        handleMillTypeSelectChange,
        handleWoodSpeciesSelectChange,
        handleClearFiltersClick,
        handleExportClick,
        searchParams,
        geolocationStatus,
        handleRequestLocationClick,
        handleRadiusSelectChange,
    } = useMills({
        millsApiUrl: page.props.millsApiUrl,
        rawStates: page.props.states,
        millTypes: page.props.millTypes,
        woodSpecies: page.props.woodSpecies,
        csrfToken: page.props.csrf_token,
    });

    /**
     * DialogDrawer stuff
     */
    const [drawerOpen, setDrawerOpen] = useState<boolean>(false);

    const drawerProps = {
        direction: "left",
        modal: true,
        container: document.getElementById('map-control-container'),
        onOpenChange: setDrawerOpen,
        autoFocus: drawerOpen,
        className: 'w-screen min-w-full max-w-full'
    } as DialogProps;

    const dialogProps = {
        modal: true,
        container: document.getElementById('map-control-container'),
        onOpenChange: setDrawerOpen,
        autoFocus: drawerOpen
    } as DialogProps;

    /**
     * make this a component
     */
    const triggerButton = (
        <Button
            className="bg-coupe border border-beluga text-beluga text-[16px] font-bold justify-self-end ml-auto rounded-sm z-20"
            id="filter-trigger"
        >
            <span className="hidden lg:inline">Filters</span>
            <SlidersHorizontalIcon
                data-icon="inline-end"                            
                className="w-6 h-6 lg:ml-2 size-1"
            />
        </Button>
    );


    return (
        <AppLayout>
            <Head title={page.props.pageTitle} />

            {/** full-width wrapper for title bar */}
            <div className="flex flex-col items-center px-4 lg:px-8 text-velvet lg:justify-center bg-lorne border-b-6">
                {/** title bar + filter controls */}
                <div className="w-full lg:max-w-7xl mx-auto flex flex-row items-center justify-between px-6 py-2">
                    <div data-thing="" className="flex flex-row gap-x-5">
                        <h1 className="font-bold text-3xl text-beluga">Mill List</h1>
                        {isLoading || isDownloading ? (
                            <Spinner data-icon="inline-end" className="ml-auto size-8 text-beluga" />
                        ) : ''}
                    </div>
                    <div data-thing="button-wrap" className="flex flex-colX flex-row gap-5">
                        <Button
                            className="bg-coupe border border-beluga text-beluga text-[16px] font-bold rounded-sm z-20"
                            id="export-trigger"
                            onClick={handleExportClick}
                            disabled={isDownloading || isLoading}
                        >
                            <span className="hidden lg:inline">Export</span>
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
                            drawerProps={drawerProps}
                            dialogHeaderProps={{
                                className: "sr-only"
                            }}
                            dialogContentProps={{
                                className: "bg-nature lg:bg-lorne z-100 border-lorne",                            
                            }}
                            dialogProps={dialogProps}
                        >
                            <MillFilters
                                textSearch={searchText}
                                states={states}
                                counties={counties}
                                millTypes={page.props.millTypes}
                                woodSpecies={page.props.woodSpecies}
                                filterResetKey={filterResetKey}
                                isLoading={isLoading}
                                isDownloading={isDownloading}
                                onTextSearchChange={handleTextSearchChange}
                                onStateSelectChange={handleStateSelectChange}
                                onCountySelectChange={handleCountySelectChange}
                                onMillTypesSelectChange={handleMillTypeSelectChange}
                                onWoodSpeciesSelectChange={handleWoodSpeciesSelectChange}
                                onClearFiltersClick={handleClearFiltersClick}
                                onExportClick={handleExportClick}
                                searchParams={searchParams}
                                geolocationStatus={geolocationStatus}
                                onRequestLocationClick={handleRequestLocationClick}
                                onRadiusSelectChange={handleRadiusSelectChange}
                                millCount={mills.length}
                            />
                        </DialogDrawer>
                    </div>
                </div>
            </div>
            {/* full screen-width wrapper */}
            <div className="flex min-h-screen flex-col items-center px-4 lg:px-8 text-velvet lg:justify-center">
                {/* content column: max-width 1280px */}
                <div className="flex flex-col lg:flex-row w-full max-w-7xl items-start justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 lg:px-5 lg:gap-5 lg:justify-between">
                    {/* <MillFilters
                        textSearch={searchText}
                        states={states}
                        counties={counties}
                        millTypes={page.props.millTypes}
                        woodSpecies={page.props.woodSpecies}
                        filterResetKey={filterResetKey}
                        isLoading={isLoading}
                        isDownloading={isDownloading}
                        onTextSearchChange={handleTextSearchChange}
                        onStateSelectChange={handleStateSelectChange}
                        onCountySelectChange={handleCountySelectChange}
                        onMillTypesSelectChange={handleMillTypeSelectChange}
                        onWoodSpeciesSelectChange={handleWoodSpeciesSelectChange}
                        onClearFiltersClick={handleClearFiltersClick}
                        onExportClick={handleExportClick}
                        searchParams={searchParams}
                        geolocationStatus={geolocationStatus}
                        onRequestLocationClick={handleRequestLocationClick}
                        onRadiusSelectChange={handleRadiusSelectChange}
                        millCount={mills.length}
                    /> */}
                    <MillList mills={mills} />
                </div>
            </div>
        </AppLayout>
    );
}
