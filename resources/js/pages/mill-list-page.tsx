// import {
//     useState
// } from "react";
import AppLayout from '@/layouts/app-layout';
import {
    type MillType,
    type PageSeoOverride,
    type State,
    type WoodSpecies,
    // type SearchParams,
} from '@/types';
import {
    usePage,
} from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { useMills } from '@/hooks/use-mills';
import MillFilters from '@/components/mill-filters';
import { MillFiltersPanel } from '@/components/mill-filters-panel';
import { useFilterPanel } from '@/hooks/use-filter-panel';
import MillList from '@/components/mill-list';
// import { 
//     Button
// } from "@/components/ui/button";
// import {
//     DialogDrawer
// } from "@/components/extend/dialog-drawer";
import {
    TitleFilterBar
} from "@/components/title-filter-bar";
// import {
//     DialogProps
// } from "vaul";
// import {
//     DownloadIcon,
//     SlidersHorizontalIcon
// } from "lucide-react";
// import { Spinner } from '@/components/ui/spinner';


export default function MillListPage() {
    const page = usePage<{
        states: State[];
        millTypes?: MillType[];
        woodSpecies?: WoodSpecies[];
        pageTitle?: string;
        pageSeo: PageSeoOverride;
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
        loadError,
        handleRetryLoadClick,
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
        handleCustomLocationSubmit,
        isGeocoding,
        geocodeError,
        locationLabel,
        handleResetLocationClick,
        isLocationActive,
        activeFilters,
        handleRemoveFilter,
        millTypeOptions,
        woodSpeciesOptions,
    } = useMills({
        millsApiUrl: page.props.millsApiUrl,
        rawStates: page.props.states,
        millTypes: page.props.millTypes,
        woodSpecies: page.props.woodSpecies,
        csrfToken: page.props.csrf_token,
    });

    const filterPanel = useFilterPanel();

    return (
        <AppLayout>
            <Seo {...page.props.pageSeo} />

            <TitleFilterBar
                headline={page.props.pageTitle || ''}
                handleExportClick={handleExportClick}
                isDownloading={isDownloading}
                isLoading={isLoading}
                loadError={loadError}
                millCount={mills.length}
                filtersOpen={filterPanel.open}
                onFiltersToggle={filterPanel.toggle}
                isDesktop={filterPanel.isDesktop}
                activeFilters={activeFilters}
                onRemoveFilter={handleRemoveFilter}
            />


            {/* full screen-width wrapper */}
            <div className="flex min-h-screen flex-col items-center px-4 lg:px-8 text-velvet lg:justify-center">
                {/* content column: max-width 1280px */}
                <div className="flex flex-col lg:flex-row w-full max-w-7xl items-start justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 lg:px-5 lg:gap-5 lg:justify-between">
                    {/**
                     * Desktop: sits beside the list inside the content column, so it never covers mills; the list's
                     * fixed-width cards wrap into fewer columns while it's open. Sticks in view while scrolling.
                     */}
                    <MillFiltersPanel
                        open={filterPanel.open}
                        onOpenChange={filterPanel.setOpen}
                        isDesktop={filterPanel.isDesktop}
                        // 26rem leaves room for two 360px cards beside it in the 80rem content column
                        className="lg:sticky lg:top-4 self-start shrink-0 w-104 mt-4 rounded-sm"
                    >
                        <MillFilters
                            textSearch={searchText}
                            states={states}
                            counties={counties}
                            millTypes={millTypeOptions}
                            woodSpecies={woodSpeciesOptions}
                            filterResetKey={filterResetKey}
                            isLoading={isLoading}
                            loadError={loadError}
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
                            onShowResultsClick={filterPanel.isDesktop ? undefined : () => filterPanel.setOpen(false)}
                            announceStatus={!filterPanel.isDesktop}
                    screenReaderHeading={filterPanel.isDesktop ? 'Filters' : undefined}
                        />
                    </MillFiltersPanel>
                    <MillList
                        mills={mills}
                        isLoading={isLoading}
                        loadError={loadError}
                        onClearFiltersClick={handleClearFiltersClick}
                        onRetryClick={handleRetryLoadClick}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
