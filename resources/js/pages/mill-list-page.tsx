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
import MillList from '@/components/mill-list';

export default function MillListPage() {
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
        filterResetKey,
        isLoading,
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
            {/* full screen-width wrapper */}
            <div className="flex min-h-screen flex-col items-center p-6 text-velvet lg:justify-center lg:p-8">
                {/* content column: max-width 1280px */}
                <div className="flex flex-col w-full max-w-7xl items-start justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 px-5">
                    <MillFilters
                        textSearch={searchText}
                        states={states}
                        counties={counties}
                        millTypes={page.props.millTypes}
                        woodSpecies={page.props.woodSpecies}
                        filterResetKey={filterResetKey}
                        isLoading={isLoading}
                        onTextSearchChange={handleTextSearchChange}
                        onStateSelectChange={handleStateSelectChange}
                        onCountySelectChange={handleCountySelectChange}
                        onMillTypesSelectChange={handleMillTypeSelectChange}
                        onWoodSpeciesSelectChange={handleWoodSpeciesSelectChange}
                        onClearFiltersClick={handleClearFiltersClick}
                    />
                    <MillList mills={mills} />
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
