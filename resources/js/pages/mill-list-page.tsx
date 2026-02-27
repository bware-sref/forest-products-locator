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
    Link,
    usePage,
} from '@inertiajs/react';
import { 
    useEffect,
    useState,    
} from 'react';

// import { Button } from '@/components/ui/button';
// import {
//     Card,
//     CardContent,
//     CardHeader,
//     CardTitle,
// } from "@/components/ui/card";
import {
    // ArrowRight,
    SearchIcon
} from 'lucide-react';
import { fetchMills } from "@/lib/api"
import { show } from '@/actions/App/Http/Controllers/MillController';
import MillFilters from '@/components/mill-filters';
import MillList from '@/components/mill-list';

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
    const pageTitle = 'Mill List';

    // states should always be the same...unless a state doesn't have a particular millType or woodSpecies?
    // map to peel off the counties before shoving into the combobox
    const states: State[] = page.props.states.map((state) => ({
        id: state.id,
        name: state.name,
        abbreviation: state.abbreviation
    }));

    const countiesByState = getCountiesByState(states);

    const [selectedState, setSelectedState] = useState<State|null>(null);
    const [counties, setCounties] = useState<County[]>([]);
    const [selectedCounty, setSelectedCounty] = useState<County|null>(null);
    const [selectedMillType, setSelectedMillType] = useState<MillType|null>(null);
    const [selectedWoodSpecies, setSelectedWoodSpecies] = useState<WoodSpecies|null>(null);

    const [mills, setMills] = useState<Mill[]>([]);
    const [searchParams, setSearchParams] = useState<Object>({});

    const handleStateSelectChange = function (stateAbbreviation: Event|string) {
        console.log('in handleStateSelectChange, stateChanged!', stateAbbreviation);
        console.log('update Counties!');
        setSearchParams({state: stateAbbreviation});
        for (const state of states) {
            if (state.abbreviation === stateAbbreviation) {
                setSelectedState(state);
                setCounties(countiesByState[stateAbbreviation]);
                break;
            }
        };
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
            console.log('mills!', result);
            setMills(result);
        });
        return () => {
            ignore = true;
        }
        /**
         * empty dependencies make it run on page load
         * but should it be watching mills?
         * or should it watch searchParameters?
         */
    }, [selectedState]); // 

    useEffect(() => {
        console.log('state changed!')
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
                        millTypes={page.props.millTypes}
                        woodSpecies={page.props.woodSpecies}
                        onStateSelectChange={handleStateSelectChange}
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
