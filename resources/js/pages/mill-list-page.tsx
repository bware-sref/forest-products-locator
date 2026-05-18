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
    } = useMills({
        millsApiUrl: page.props.millsApiUrl,
        rawStates: page.props.states,
        millTypes: page.props.millTypes,
        woodSpecies: page.props.woodSpecies,
        csrfToken: page.props.csrf_token,
    });

    return (
        <AppLayout>
            <Head title={page.props.pageTitle} />
            {/* full screen-width wrapper */}
            <div className="flex min-h-screen flex-col items-center px-4 lg:p-8 text-velvet lg:justify-center">
                {/* content column: max-width 1280px */}
                <div className="flex flex-col lg:flex-row w-full max-w-7xl items-start justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 lg:px-5 lg:gap-5 lg:justify-between">
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
                    />
                    <MillList mills={mills} />
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
