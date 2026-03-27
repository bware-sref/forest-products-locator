import AppLayout from '@/layouts/app-layout';
import {
    type Mill,
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
    }>();

    const {
        mills,
        states,
        counties,
        searchText,
        searchParams,
        filterResetKey,
        handleTextSearchChange,
        handleStateSelectChange,
        handleCountySelectChange,
        handleMillTypeSelectChange,
        handleWoodSpeciesSelectChange,
        handleClearFiltersClick,
    } = useMills({
        millsApiUrl: page.props.millsApiUrl,
        rawStates: page.props.states,
        millTypes: page.props.millTypes,
        woodSpecies: page.props.woodSpecies,
    });

    return (
        <AppLayout>
            <Head title={page.props.pageTitle} />
            {/* 
                full screen-width wrapper
                height should be screen-height minus the header height, h-20 (~5rem) 
            */}
            <div className="flex flex-col w-full h-[calc(100vh-4rem)] items-center text-velvet lg:justify-center">
                {/* content column: max-width 1280px */}
                <div className="flex flex-col w-full items-start justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <MillMap mills={mills}>
                        <MillFilters
                            headline={page.props.pageTitle}
                            textSearch={searchText}
                            searchParams={searchParams}
                            filterResetKey={filterResetKey}
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
                        />
                    </MillMap>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
