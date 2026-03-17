import AppLayout from '@/layouts/app-layout';
import { 
    type County,
    type Mill,
    type MillType,
    type State,
    type WoodSpecies,
    type SearchParams,
} from '@/types';
import { 
    Head,
    usePage,
} from '@inertiajs/react';
import { 
    ChangeEvent,
    MouseEventHandler,
    useCallback,
    useEffect,
    useState,
    // useMemo,
} from 'react';

import { fetchMills } from "@/lib/api"
import MillFilters from '@/components/mill-filters';
import MillList from '@/components/mill-list';
// custom hook to prevent firing events too frequently
import useDebounce from '@/lib/useDebounce';

/**
 * type for mapping counties by state
 * I wonder if I can just do an inline type?
 */
type CountiesByState = Record<string, County[]>;

/**
 * Two, no Three things:
 * 1) update the State selector to use state.id instead of state.abbreviation
 * 2) update this method to key counties by state.id instead of state.abbreviation
 * 3) update API search to use state.id instead of state.abbreviation
 * @param states 
 * @returns 
 */
const getCountiesByState = function (states: State[]) {
    const countiesByState: CountiesByState = {};
    for (const state of states) {
        if (typeof state.counties !== 'undefined') {
            // key by state.id instead of state.abbreviation because stuff
            countiesByState[state.id] = state.counties || [];
        }
    }
    return countiesByState;
}

export default function MillListPage() {
    // const page = usePage<SharedData>();
    const page = usePage<{
        // mills: Mill[];
        states: State[];
        // counties: County[];
        millTypes?: MillType[];
        woodSpecies?: WoodSpecies[];
        pageTitle?: string;
        millsApiUrl: string;
        // do we need to allow for GET search parameters to be passed from the controller?
    }>();
    const pageTitle = page.props.pageTitle;

    // states should always be the same...unless a state doesn't have a particular millType or woodSpecies?
    // map to peel off the counties before shoving into the combobox
    // does it make more sense to strip the counties on the server side?
    const states: State[] = page.props.states.map((state) => ({
        id: state.id,
        name: state.name,
        abbreviation: state.abbreviation,
        value: state.value || String(state.id),
        label: state.label || state.name,
    }));

    const countiesByState: CountiesByState = getCountiesByState(page.props.states);    

    const [searchText, setSearchText] = useState<string>('');
    const [selectedState, setSelectedState] = useState<State|null>(null);
    const [counties, setCounties] = useState<County[]>([]);
    const [selectedCounty, setSelectedCounty] = useState<County|null>(null);
    const [selectedMillType, setSelectedMillType] = useState<MillType|null>(null);
    const [selectedWoodSpecies, setSelectedWoodSpecies] = useState<WoodSpecies|null>(null);

    const [mills, setMills] = useState<Mill[]>([]);
    // should we initialize searchParams with an empty object, or an object with all the keys having null values?
    const [searchParams, setSearchParams] = useState<SearchParams>({});

    /**
     * Handle typing events in the text search field.
     * @param event 
     */
    const handleTextSearchChange = function (event: ChangeEvent<HTMLInputElement>) {
        console.log(`textSearchChange event! value: ${event.target.value}`);
        debouncedTextSearch();
        setSearchText(event.target.value);
    }

    const textSearchCallback = useCallback(() => {
        console.log(`(debounced?) textSearchCallback textSearch: ${searchText}`);
    }, [searchText]);

    // debounce text input changes to prevent excess API calls
    // actually, if there's a search button, we shouldn't search immediately when form element values change...
    const debouncedTextSearch = useDebounce(textSearchCallback, 500);

    // update to use state.id instead of state.abbreviation
    // also need to update the API and Request to accept the state ID instead
    const handleStateSelectChange = function (optionValue: Event|string) {
        // searchParams should only be set if we find the state, no?
        setSearchParams({state: optionValue});
        for (const state of states) {
            // if (state.abbreviation === stateAbbreviation) {
            // if (String(state.id) === stateAbbreviation) {
            if (state.value === optionValue) {
                setSelectedState(state);
                setCounties(countiesByState[optionValue]);
                /**
                 * do we need to update the millTypes and WoodSpecies?
                 * Only if we have millTypes and woodSpecies available by state.
                 */
                break;
            }
        };
    }

    const handleCountySelectChange = function (countyId: string) {
        
        console.log('in handleCountySelectChange...', countyId);
        console.log('need to look up the County in the list of counties by state...or just in the list of counties...');
        // or just in the list of counties...
        const county = counties.find((c) => c.id == parseInt(countyId)) || null;
        if (county !== selectedCounty) {
            setSelectedCounty(county);
        }
    }

    const handleMillTypeSelectChange = function (millTypeId: string) {
        console.log('in handleMillTypeSelectChange...', millTypeId);
        const millType = page.props.millTypes ? 
            (page.props.millTypes.find((mt) => mt.id == parseInt(millTypeId)) || null) : null;
        if (millType !== selectedMillType) {
            setSelectedMillType(millType);
        }
    }

    const handleWoodSpeciesSelectChange = function (woodSpeciesId: string) {
        console.log('in handleWoodSpeciesSelectChange: ', woodSpeciesId);
        const woodSpecies = page.props.woodSpecies ? 
            (page.props.woodSpecies.find((w) => w.id == parseInt(woodSpeciesId)) || null) : null;
        if (woodSpecies !== selectedWoodSpecies) {
            setSelectedWoodSpecies(woodSpecies);
        }
    }

    /**
     * It's possible this should only exist within the component.
     * @param event 
     */
    const handleClearFiltersClick: MouseEventHandler<HTMLButtonElement> = function (event) {
        console.log('Clear Filters clicked!', event);
        setSearchText('');
        setSelectedState(null);
        setSelectedCounty(null);
        setSelectedMillType(null);
        setSelectedWoodSpecies(null);
    }

    /**
     * Here's where we fetch the mill data on page load!
     */
    useEffect(() => {
        /**
         * react docs recommend using an ignore flag to prevent useEffect running twice
         * fun fact, when running in development mode (i.e., npm run dev), React does all fetch requests twice!
         */
        let ignore = false;
        // setMills([]);
        fetchMills(page.props.millsApiUrl, searchParams).then(result => {
            if (!ignore) {
                // console.log('mills!', result);
                setMills(result);
            }
        });
        return () => {
            ignore = true;
        }
        /**
         * empty dependencies make it run on page load
         * but should it be watching mills?
         * or should it watch searchParameters?
         * or waiting for a form submission?
         */
    // es-lint says millsApiUrl and searchParams are dependencies of useEffect, so add them or remove the array.
    // This method does seem like it should watch searchParams rather than just selectedState, but maybe not?
    // if we use a submit button instead of immediately refreshing the mill list, this is not the same issue.
    }, [selectedState]);

    useEffect(() => {
        console.log('state changed!');
        console.log('this means we need to update the counties, mill types, and wood species');
    }, [selectedState]);

    /**
     * Render!
     */
    return (
        <AppLayout>
            <Head title={pageTitle} />
            {/**
             * full screen-width wrapper
             */}
            <div className="flex min-h-screen flex-col items-center p-6 text-velvet lg:justify-center lg:p-8">
                {/**
                 * content column: max-width 1280px
                 */}
                <div className="flex flex-col w-full max-w-7xl items-start justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 px-5">
                    {/**
                     * Mill Filters
                     */}
                    <MillFilters
                        textSearch={textSearch}
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
                    {/**
                     * Mill List
                     */}
                    <MillList mills={mills} />
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
