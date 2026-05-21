import {
    useMemo,
    useState,
} from "react";
import AppLayout from '@/layouts/app-layout';
import {
    type MillType,
    type State,
    type WoodSpecies,
} from '@/types';
import {
    Head,
    usePage,
} from '@inertiajs/react';
import { useMills } from '@/hooks/use-mills';
import MillFilters from '@/components/mill-filters';
import MillMap from '@/components/mill-map';
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
// import Map so we can use it as a type
import { Map } from "leaflet";

export default function MillMapPage() {
    const page = usePage<{
        states: State[];
        millTypes?: MillType[];
        woodSpecies?: WoodSpecies[];
        pageTitle?: string;
        millsApiUrl: string;
        csrf_token: string;
    }>();

    /**
     * useMills hook
     */
    const {
        mills,
        states,
        counties,
        searchText,
        searchParams,
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
        geolocationStatus,
        handleRequestLocationClick,
        handleRadiusSelectChange,
        coordinates,
        radius,
    } = useMills({
        millsApiUrl: page.props.millsApiUrl,
        rawStates: page.props.states,
        millTypes: page.props.millTypes,
        woodSpecies: page.props.woodSpecies,
        csrfToken: page.props.csrf_token,
    });

    /**
     * catch the map so we can use external controls!
     * need to use map or else it makes no sense to save its state
     */
    const [map, setMap] = useState<Map | null>(null);

    /**
     * We need to add position and radius to the return values from useMills and use them to display the following:
     * - successful location request should add a map pin at the users location
     * - selecting a radius should add a circle around the user's location
     */
    const displayMap = useMemo(() => (
        <MillMap 
            mills={mills}
            className="lg:min-h-screen"
            ref={setMap}
            radius={radius}
            coordinates={coordinates}
        ></MillMap>
    ), [mills, radius, coordinates]);

    /**
     * DialogDrawer stuff
     */
    const [drawerOpen, setDrawerOpen] = useState<boolean>(false);

    const drawerProps = {
        direction: "left",
        modal: true,
        onOpenChange: setDrawerOpen,
        autoFocus: drawerOpen,
        className: 'w-screen min-w-full max-w-full'
    } as DialogProps;

    const dialogProps = {
        modal: true,
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
            <span className="sr-only lg:not-sr-only"><span className="sr-only">Toggle </span>Filters</span>
            <SlidersHorizontalIcon
                data-icon="inline-end"                            
                className="w-6 h-6 lg:ml-2 size-1"
            />
        </Button>
    );


    return (
        <AppLayout>
            <Head title={page.props.pageTitle} />
            {/* 
                full screen-width wrapper
                height should be screen-height minus the header height, h-20 (~5rem) 
            */}
            {/** full-width wrapper for title bar */}
            <div className="flex flex-col items-center px-4 lg:px-6 text-velvet lg:justify-center border-b-6 bg-lorne">
                {/** title bar + filter controls */}
                <div className="w-full lg:max-w-7xl mx-auto flex flex-row items-center justify-between px-6 md:px-0 py-2">
                    <div data-thing="" className="flex flex-row gap-x-5">
                        <h1 className="font-bold text-3xl text-beluga">Mill Map</h1>
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

            {/**
             * Actual map wrap
             */}
            <div
                data-thing="map-page-wrap" 
                className="flex flex-col w-full h-[calc(100vh-4rem)] items-center text-velvet lg:justify-center">
                {/* content column: max-width 1280px */}
                <div
                    data-thing="map-wrap" 
                    className="flex flex-col w-full items-start justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    {displayMap}
                </div>
            </div>
        </AppLayout>
    );
}
