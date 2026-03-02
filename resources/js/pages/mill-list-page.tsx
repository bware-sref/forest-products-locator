import AppLayout from '@/layouts/app-layout';
import { 
    type County,
    type Mill,
    type MillType,
    type State,
    type WoodSpecies,
} from '@/types';
import { 
    Head,
    usePage,
} from '@inertiajs/react';
import { 
    useEffect,
    useState,    
} from 'react';

import { fetchMills } from "@/lib/api"
import MillFilters from '@/components/mill-filters';
import MillList from '@/components/mill-list';


/**
 * type for mapping counties by state
 * I wonder if I can just do an inline type?
 */
type CountiesByState = Record<string, County[]>;

const getCountiesByState = function (states: State[]) {
    const countiesByState: CountiesByState = {};
    for (const state of states) {
        if (typeof state.counties !== undefined) {
            countiesByState[state.abbreviation] = state.counties || [];
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
    }>();
    const pageTitle = page.props.pageTitle;

    // states should always be the same...unless a state doesn't have a particular millType or woodSpecies?
    // map to peel off the counties before shoving into the combobox
    // does it make more sense to strip the counties on the server side?
    const states: State[] = page.props.states.map((state) => ({
        id: state.id,
        name: state.name,
        abbreviation: state.abbreviation
    }));

    const countiesByState: CountiesByState = getCountiesByState(page.props.states);    

    const [selectedState, setSelectedState] = useState<State|null>(null);
    const [counties, setCounties] = useState<County[]>([]);
    const [selectedCounty, setSelectedCounty] = useState<County|null>(null);
    const [selectedMillType, setSelectedMillType] = useState<MillType|null>(null);
    const [selectedWoodSpecies, setSelectedWoodSpecies] = useState<WoodSpecies|null>(null);

    const [mills, setMills] = useState<Mill[]>([]);
    const [searchParams, setSearchParams] = useState<Object>({});

    const handleStateSelectChange = function (stateAbbreviation: Event|string) {
        setSearchParams({state: stateAbbreviation});
        for (const state of states) {
            if (state.abbreviation === stateAbbreviation) {
                setSelectedState(state);
                setCounties(countiesByState[stateAbbreviation]);
                /**
                 * do we need to update the millTypes and WoodSpecies?
                 */
                break;
            }
        };
    }

    const handleCountySelectChange = function (countyId: Event|string) {
        console.log('in handleCountySelectChange...', countyId);
        // setSelectedCounty(countyId)
    }

    const handleMillTypeSelectChange = function (millTypeId: Event|string) {
        console.log('in handleMillTypeSelectChange...', millTypeId);
    }

    const handleWoodSpeciesSelectChange = function (woodSpeciesId: Event|string) {
        console.log('in handleWoodSpeciesSelectChange', woodSpeciesId);
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
    }, [selectedState]); // 

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
                        states={states}
                        counties={counties}
                        millTypes={page.props.millTypes}
                        woodSpecies={page.props.woodSpecies}
                        onStateSelectChange={handleStateSelectChange}
                        onCountySelectChange={handleCountySelectChange}
                        onMillTypesSelectChange={handleMillTypeSelectChange}
                        onWoodSpeciesSelectChange={handleWoodSpeciesSelectChange}
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
