import {
    // MouseEvent,
    // useCallback,
    lazy,
    Suspense,
    useEffect,
    useMemo,
    useState,
} from "react";
import AppLayout from '@/layouts/app-layout';
import { ClientOnly } from '@/components/client-only';
import {
    type MillType,
    type PageSeoOverride,
    type State,
    type WoodSpecies,
} from '@/types';
import {
    usePage,
} from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { useMills } from '@/hooks/use-mills';
import MillFilters from '@/components/mill-filters';

// MillMap (and everything it pulls in transitively — react-leaflet, leaflet
// itself) touches window/document at import time, which crashes SSR. Lazy
// loading it means the module is only ever fetched in the browser, since
// ClientOnly keeps it from being rendered (and thus imported) server-side.
const MillMap = lazy(() => import('@/components/mill-map'));
// import {
//     Button
// } from "@/components/ui/button";
// import {
//     DialogDrawer
// } from "@/components/extend/dialog-drawer";
// import {
//     DialogProps
// } from "vaul";
// import {
//     DownloadIcon,
//     SlidersHorizontalIcon
// } from "lucide-react";
// import { Spinner } from '@/components/ui/spinner';
import {
    TitleFilterBar
} from "@/components/title-filter-bar";

// type-only: erased at compile time, so this doesn't pull leaflet into SSR
import type { Map } from "leaflet";

export default function MillMapPage() {
    const page = usePage<{
        states: State[];
        millTypes?: MillType[];
        woodSpecies?: WoodSpecies[];
        pageTitle?: string;
        pageSeo: PageSeoOverride;
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
        handleCustomLocationSubmit,
        isGeocoding,
        geocodeError,
        locationLabel,
        handleResetLocationClick,
        isLocationActive,
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
     * it might not make sense to save map's state
     * let's see what happens if we remove it...
     */
    const [map, setMap] = useState<Map | null>(null);

    /**
     * We need to add position and radius to the return values from useMills and use them to display the following:
     * - successful location request should add a map pin at the users location
     * - selecting a radius should add a circle around the user's location
     * - yeah, we don't seem to need to save user's location
     */
    const displayMap = useMemo(() => (
        <ClientOnly fallback={<div className="lg:min-h-screen w-full" />}>
            <Suspense fallback={<div className="lg:min-h-screen w-full" />}>
                <MillMap
                    mills={mills}
                    className="lg:min-h-screen"
                    radius={radius}
                    coordinates={coordinates}
                    onMapReady={setMap}
                />
            </Suspense>
        </ClientOnly>
    ), [mills, radius, coordinates]);

    // handle map focus when coordinates are found
    useEffect(() => {
        if (map && coordinates) {
            // console.log(`flying to ${coordinates?.lat} and ${coordinates?.lng}`);
            map?.flyTo([coordinates?.lat, coordinates?.lng], 10);
        }
        // console.log('map: ', map);
    }, [coordinates, map]);

    return (


        <AppLayout>
            <Seo {...page.props.pageSeo} />

            <TitleFilterBar
                // onClickCapture={}
                headline={page.props.pageTitle || ''}
                isDownloading={isDownloading}
                isLoading={isLoading}
                handleExportClick={handleExportClick}
                millCount={mills.length}
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
                    // onExportClick={handleExportClick}
                    searchParams={searchParams}
                    geolocationStatus={geolocationStatus}
                    onRequestLocationClick={handleRequestLocationClick}
                    onRadiusSelectChange={handleRadiusSelectChange}
                    isLocationActive={isLocationActive}
                    onCustomLocationSubmit={handleCustomLocationSubmit}
                    onResetLocationClick={handleResetLocationClick}
                    isGeocoding={isGeocoding}
                    geocodeError={geocodeError}
                    locationLabel={locationLabel}
                    millCount={mills.length}
                />

            </TitleFilterBar>

            {/**
             * full screen-width wrapper
             * height should be screen-height minus the header height, h-20 (~5rem) 
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
