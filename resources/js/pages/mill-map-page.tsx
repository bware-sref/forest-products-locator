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

export default function MillMapPage() {
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
    } = useMills({
        millsApiUrl: page.props.millsApiUrl,
        rawStates: page.props.states,
        millTypes: page.props.millTypes,
        woodSpecies: page.props.woodSpecies,
        csrfToken: page.props.csrf_token,
    });

    /**
     * This was a stab at external controls.
     */
    // const triggerTrigger = () => {
    //     console.log('dispatching a click event...');
    //     const thing = document.getElementById('filter-trigger');
    //     if (thing) {
    //         console.log('found thing; dispatching event...', thing);
    //         // thing.dispatchEvent(new Event('click'));
    //         thing.click();
    //     } else {
    //         console.log('did not find button, mang')
    //     }
    //     console.log('should have happened by now...')
    // };

    return (
        <AppLayout>
            <Head title={page.props.pageTitle} />
            {/* 
                full screen-width wrapper
                height should be screen-height minus the header height, h-20 (~5rem) 
            */}
                {/* <div data-thing="title and filter trigger"
                    className="w-full lg:max-w-7xl mx-auto bg-lorne px-6 flex flex-row items-center justify-self-start"
                >
                    <h1 className="font-extrabold text-beluga text-3xl py-2">{page.props.pageTitle}</h1>
                    <Button
                            className="bg-coupe border border-beluga text-beluga text-[16px] font-bold justify-self-end ml-auto rounded-sm z-10"
                            id="filter-trigger-trigger"
                            aria-controls="filter-trigger"
                            onClick={triggerTrigger}
                        >
                            Filters
                            <SlidersHorizontalIcon
                                data-icon="inline-end"                            
                                className="w-6 h-6 ml-2 size-1"
                            />
                        </Button>
                </div> */}
            <div
                data-thing="map-page-wrap" 
                className="flex flex-col w-full h-[calc(100vh-4rem)] items-center text-velvet lg:justify-center">
                {/* content column: max-width 1280px */}
                <div
                    data-thing="map-wrap" 
                    className="flex flex-col w-full items-start justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <MillMap mills={mills} className="lg:min-h-screen">
                        <MillFilters
                            headline={"Mill Filters"}
                            textSearch={searchText}
                            filterResetKey={filterResetKey}
                            isLoading={isLoading}
                            isDownloading={isDownloading}
                            states={states}
                            counties={counties}
                            millTypes={page.props.millTypes}
                            woodSpecies={page.props.woodSpecies}
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
                            className="z-100 relative"
                        />
                    </MillMap>
                </div>
            </div>
        </AppLayout>
    );
}
