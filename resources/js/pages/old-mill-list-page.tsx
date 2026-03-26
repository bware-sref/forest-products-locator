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
    useMemo,
} from 'react';
import { debounce } from 'lodash-es';
import { fetchMills } from "@/lib/api"
import MillFilters from '@/components/mill-filters';
import MillList from '@/components/mill-list';

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
    // counties are attached to states
    const page = usePage<{
        states: State[];
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
    // :shrug:
    // it makes sense to extract this method from the render if possible
    // we could also combine this with separating counties from states
    const states: State[] = page.props.states.map((state) => ({
        id: state.id,
        name: state.name,
        abbreviation: state.abbreviation,
        value: state.value || String(state.id),
        label: state.label || state.name,
    }));

    /**
     * Do we need to do this on every re-render?
     * Perhaps useMemo()?
     * Or useRef?
     */
    const countiesByState: CountiesByState = getCountiesByState(page.props.states);    

    // state variables
    // I wonder...would using a single state variable for searchParams instead of all these separate values work?
    // by "work", I mean "functional equivalence"
    const [searchText, setSearchText] = useState<string>('');
    const [selectedState, setSelectedState] = useState<State|null>(null);
    const [counties, setCounties] = useState<County[]>([]);
    const [selectedCounty, setSelectedCounty] = useState<County|null>(null);
    const [selectedMillType, setSelectedMillType] = useState<MillType|null>(null);
    const [selectedWoodSpecies, setSelectedWoodSpecies] = useState<WoodSpecies|null>(null);

    // mills still needs to be a distinct state variable
    const [mills, setMills] = useState<Mill[]>([]);
    // should we initialize searchParams with an empty object, or an object with all the keys having null values?
    const [searchParams, setSearchParams] = useState<SearchParams>({});

    /**
     * Collect input values and assemble into a SearchParams object.
     * React compiler said it needs to be wrapped in useCallback().
     * 
     * @param p - any search parameters that should override the current values
     * @returns SearchParams
     */
    const buildSearchParams = useCallback(function(p?: SearchParams): SearchParams {
        // intialize in case no params
        const params: SearchParams = p || {};

        // need to check existence of state variable and state variable value before assigning
        // also, we now need to check if this value was passed via params
        if (!params.state && selectedState && selectedState.value) {
            params.state = selectedState.value;
        }
        if (!params.county && selectedCounty && selectedCounty.value) {
            params.county = selectedCounty.value;
        }
        if (!params.millType && selectedMillType && selectedMillType.value) {
            params.millType = selectedMillType.value;
        }
        if (!params.woodSpecies && selectedWoodSpecies && selectedWoodSpecies.value) {
            params.woodSpecies = selectedWoodSpecies.value;
        }
        // oh right, searchText is the odd ball WRT having a value member
        if (searchText) {
            params.q = searchText;
        }
        return params;
    }, [selectedState, selectedCounty, selectedMillType, selectedWoodSpecies, searchText]);

    /**
     * Text input change handler.
     * We debounce this method before attaching it to an element.
     *
     * @param value     - the value of the text input
     */
    const textSearchCallback = useCallback((value: string) => {
        const newParams = buildSearchParams({
            q: value,
        });
        if ('' === value && newParams.q) {
            delete newParams.q;
        }
        setSearchParams(newParams);
    }, [buildSearchParams]);

    /**
     * Holy mother of pony!
     * useMemo() (rather than useCallback()) was apparently the solution!
     */
    const debouncedTextSearch = useMemo(() =>
        debounce((value: string) => {
            textSearchCallback(value);
        }, 500)
    , [textSearchCallback]);

    /**
     * Handle typing events in the text search field.
     * @param event 
     */
    const handleTextSearchChange = function (event: ChangeEvent<HTMLInputElement>) {
        setSearchText(event.target.value);
        debouncedTextSearch(event.target.value);
    }    

    /**
     * Handle changes to the State "select/combobox"
     * 
     * @param optionValue   - the currently selected State
     * @returns
     */
    const handleStateSelectChange = function (optionValue: string) {
        // abort if value is unchanged
        if (selectedState && selectedState.value == optionValue) {
            return;
        }

        // handle clearing state value
        if ('' == optionValue) {
            // if no State selected, clear the county list (which should also trigger disabling the field)
            setCounties([]);

            // build new search parameters then delete the items we don't need before passing to setSearchParams().
            // aside: perhaps buildSearchParams should remove null values since they cause the API to return empty
            const newParams = buildSearchParams();
            if (newParams.state) {
                delete newParams.state;
            }
            if (newParams.county) {
                delete newParams.county;
            }

            setSearchParams(newParams);
            return;
        }

        // if state has actually been changed and it's not empty, loop over list of states to find the 
        // new state's counties.
        for (const state of states) {
            if (state.value === optionValue) {
                setSelectedState(state);
                setCounties(countiesByState[optionValue]);
                // update searchParams without using useEffect!
                setSearchParams(buildSearchParams({
                    state: optionValue,
                }));
                /**
                 * do we need to update the millTypes and WoodSpecies?
                 * Only if we have millTypes and woodSpecies available by state.
                 */
                break;
            }
        };
    }

    /**
     * Handle changes for the County input
     * @param countyId 
     */
    const handleCountySelectChange = function (countyId: string) {
        // handle clearing the county
        if ('' == countyId) {
            // build new search params then delete county
            const newParams = buildSearchParams();
            if (newParams.county) {
                delete newParams.county;
            }
            setSearchParams(newParams);
            return;
        }

        // if we have a non-empty county, update search params
        const county = counties.find((c) => c.id == parseInt(countyId)) || null;
        if (county !== selectedCounty) {
            setSelectedCounty(county);
            // update searchParams without using useEffect!
            setSearchParams(buildSearchParams({
                county: countyId,
            }));
        }
    }

    /**
     * Handle changes to millType
     * @param millTypeId 
     */
    const handleMillTypeSelectChange = function (millTypeId: string) {
        // handle clearing
        if ('' == millTypeId) {
            // es-lint doesn't like destructuring to remove object members
            // so rebuild search params and then delete millType
            const newParams = buildSearchParams();
            if (newParams.millType) {
                delete newParams.millType;
            }
            setSearchParams(newParams);
            return;
        }

        const millType = page.props.millTypes ? 
            (page.props.millTypes.find((mt) => mt.id == parseInt(millTypeId)) || null) : null;
        if (millType !== selectedMillType) {
            setSelectedMillType(millType);
            // update searchParams without using useEffect!
            setSearchParams(buildSearchParams({
                millType: millTypeId,
            }));
        }
    }

    /**
     * Handle changes to woodSpecies
     * @param woodSpeciesId 
     */
    const handleWoodSpeciesSelectChange = function (woodSpeciesId: string) {
        if ('' == woodSpeciesId) {
            // es-lint doesn't like destructuring to remove object members
            const newParams = buildSearchParams();
            if (newParams.woodSpecies) {
                delete newParams.woodSpecies;
            }
            setSearchParams(newParams);
            return;
        }

        const woodSpecies = page.props.woodSpecies ? 
            (page.props.woodSpecies.find((w) => w.id == parseInt(woodSpeciesId)) || null) : null;
        if (woodSpecies !== selectedWoodSpecies) {
            setSelectedWoodSpecies(woodSpecies);
            // update searchParams without using useEffect!
            setSearchParams(buildSearchParams({
                woodSpecies: woodSpeciesId,
            }));
        }
    }

    /**
     * Handle clearing all filters
     * @param event 
     */
    const handleClearFiltersClick: MouseEventHandler<HTMLButtonElement> = function (event) {
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
        // setting mills as empty list would trigger the "Loading" state of the millList
        fetchMills(page.props.millsApiUrl, searchParams).then(result => {
            if (!ignore) {
                // should we check for errors before setting the mill list?
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
    }, [page.props.millsApiUrl, searchParams]);



    /**
     * Render!
     * Except the entire MillListPage() is actually considered render
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
                     * may want to rename MillFilters component textSearch parameter to match
                     */}
                    <MillFilters
                        textSearch={searchText}
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
