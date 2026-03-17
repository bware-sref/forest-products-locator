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
import { unset } from 'lodash-es';

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
    const handleStateSelectChange = function (optionValue: string) {
        // console.log('handlingStateSelectChange...', {selectedState, optionValue});
        // searchParams should only be set if we find the state, no?
        // Setting searchParams becomes the thing that triggers the API call.
        // also, we should only setSearchParams if we find the state value and the state value is different.
        // or, if state is cleared!
        if (selectedState && selectedState.value == optionValue) {
            // console.log('selectedState has not changed...', {selectedState, optionValue});
            return;
        }

        // handle clearing state value
        if ('' == optionValue) {
            // console.log('clearing selectedState: ', {optionValue});
            // I think setting state already happened
            // setSelectedState(null);
            setCounties([]);
            // const newParams = {
            //     state: '', //null,
            //     county: '', //null,
            // };
            // console.log('attempting to build params from ', {newParams});
            // console.log('that is weird. newParams has county');
            // I wonder if we build new params and then unset the fields we want to clear before passing on the setSearchParams()
            // probably doing extra re-renders
            // destructuring to remove object properties?
            // const newParams = buildSearchParams();
            // if (newParams.state) {
            //     delete newParams.state;
            // }
            // if (newParams.county) {
            //     delete newParams.county;
            // }
            const {state, county, ...newParams} = buildSearchParams();
            // unset(sp, 'county');
            setSearchParams(newParams);
            return;
        }
        // pretty sure that removing setSearchParams is going to cause the mill list to stop updating...
        // yep, good guess.
        // setSearchParams({state: optionValue});

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
                // note that selectedState will still have the previous state at this point.
                // console.log('selectedState!', selectedState);
                break;
            }
        };
    }

    /**
     * This could probably be further simplified if selectedCounty was not a County
     * @param countyId 
     */
    const handleCountySelectChange = function (countyId: string) {        
        // console.log('in handleCountySelectChange...', countyId);
        // console.log('need to look up the County in the list of counties by state...or just in the list of counties...');
        if ('' == countyId) {
            // I think nulling this value may ahve already happened...
            // setSelectedCounty(null);
            const {county, ...newParams} = buildSearchParams();
            setSearchParams(newParams);
            return;
        }
        // or just in the list of counties...
        const county = counties.find((c) => c.id == parseInt(countyId)) || null;
        if (county !== selectedCounty) {
            // console.log('county !== selectedCounty, updating: ', {county, selectedCounty});
            setSelectedCounty(county);
            // update searchParams without using useEffect!
            setSearchParams(buildSearchParams({
                county: countyId,
            }));
        }
    }

    /**
     * Same old, probably simpler to make selectedMillType an int or string instead of a MillType
     * @param millTypeId 
     */
    const handleMillTypeSelectChange = function (millTypeId: string) {
        if ('' == millTypeId) {
            const {millType, ...newParams} = buildSearchParams();
            setSearchParams(newParams);
            return;
        }
        // console.log('in handleMillTypeSelectChange...', millTypeId);
        const millType = page.props.millTypes ? 
            (page.props.millTypes.find((mt) => mt.id == parseInt(millTypeId)) || null) : null;
        if (millType !== selectedMillType) {
            // console.log('millType changed!', {millType, selectedMillType});
            setSelectedMillType(millType);
            // update searchParams without using useEffect!
            setSearchParams(buildSearchParams({
                millType: millTypeId,
            }));
        }
    }

    /**
     * Same old, probably simpler to make selectedWoodSpecies an int or string instead of a WoodSpecies
     * @param woodSpeciesId 
     */
    const handleWoodSpeciesSelectChange = function (woodSpeciesId: string) {
        if ('' == woodSpeciesId) {
            const {woodSpecies, ...newParams} = buildSearchParams();
            setSearchParams(newParams);
            return;
        }
        // console.log('in handleWoodSpeciesSelectChange: ', woodSpeciesId);
        const woodSpecies = page.props.woodSpecies ? 
            (page.props.woodSpecies.find((w) => w.id == parseInt(woodSpeciesId)) || null) : null;
        if (woodSpecies !== selectedWoodSpecies) {
            // console.log('woodSpecies changed!', {woodSpecies, selectedWoodSpecies});
            setSelectedWoodSpecies(woodSpecies);
            // update searchParams without using useEffect!
            setSearchParams(buildSearchParams({
                woodSpecies: woodSpeciesId,
            }));
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
     * Collect input values and assemble into a SearchParams object.
     * Hmm...when this gets invoked, the respective selected<T> values haven't been updated.
     * Or at least the most recently updated hasn't been.
     * I guess we need to pass the most recently updated value to this method?
     * @param p
     * @returns SearchParams
     */
    const buildSearchParams = function(p?: SearchParams): SearchParams {
        // const params: SearchParams = {};
        // intialize in case no params
        const params: SearchParams = p || {};

        // console.log('buildingSearchParams...', {
        //     p,
        //     params,
        //     searchParams,
        //     searchText,
        //     selectedState,
        //     selectedCounty,
        //     selectedMillType,
        //     selectedWoodSpecies
        // });

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
        // console.log('built search params: ', params);
        return params;
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
        // setMills([]);
        fetchMills(page.props.millsApiUrl, searchParams).then(result => {
            if (!ignore) {
                // console.log('mills!', result);
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
    // }, [selectedState]);
    }, [page.props.millsApiUrl, searchParams]);

    // make this effect depend on all the selected input values
    // es-lint thinks I don't need an effect for this
    // more specifically, it doesn't like that I call setSearchParams() in useEffect()
    // I think the idea is that the event handlers for each input should trigger updating searchParams
    // instead of using useEffect to do it.
    // useEffect(() => {
    //     // just add all the values to searchParams and see what happens
    //     // empty result happens
    //     // we may need to check differences between current searchParams and the dependent values
    //     // console.log('when usingEffect to try to fetch mills...', {
    //     //     searchParams,
    //     //     searchText,
    //     //     selectedState,
    //     //     selectedCounty,
    //     //     selectedMillType,
    //     //     selectedWoodSpecies
    //     // });
    //     const params: SearchParams = {};
    //     // selectedValue, searchParamKey
    //     // need to check existence of state variable, state variable value and searchParams.key
    //     // before comparing to searchParams.
    //     // do we even care if they're different from the searchParams values (if any)?
    //     // probably not, but let's do overkill first
    //     if (selectedState && selectedState.value) {// && searchParams.state && selectedState.value !== searchParams.state) {
    //         params.state = selectedState.value;
    //     }
    //     if (selectedCounty && selectedCounty.value) { // && selectedCounty.value !== searchParams.county) {
    //         params.county = selectedCounty.value;
    //     }
    //     if (selectedMillType && selectedMillType.value) {
    //         params.millType = selectedMillType.value;
    //     }
    //     if (selectedWoodSpecies && selectedWoodSpecies.value) {
    //         params.woodSpecies = selectedWoodSpecies.value;
    //     }
    //     // oh right, searchText is the odd ball WRT having a value member
    //     if (searchText) {
    //         params.q = searchText;
    //     }
    //     console.log('setting search params: ', params);
    //     setSearchParams(params);
    //     // setSearchParams({
    //     //     q: searchText,
    //     //     state: selectedState,
    //     //     county: selectedCounty,
    //     //     millType: selectedMillType,
    //     //     woodSpecies: selectedWoodSpecies,
    //     // });

    // }, [searchText, selectedState, selectedCounty, selectedMillType, selectedWoodSpecies]);


    // useEffect(() => {
    //     console.log('state changed!');
    //     console.log('this means we need to update the counties, mill types, and wood species');
    // }, [selectedState]);

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
                        // clearing filters doesn't do what I want
//                        onClearState={handleClearAllOptions}
                        // onClearCounty={handleClearAllOptions}
                        // onClearMillType={handleClearAllOptions}
                        // onClearWoodSpecies={() => handleWoodSpeciesSelectChange('')}
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
