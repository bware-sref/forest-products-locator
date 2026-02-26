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

interface Dictionary<T> {
    [key: string]: T | undefined;
}
const getCountiesByState = function (states: State[]) {
    let countiesByState: Dictionary<County[]> = {};
    for (const state of states) {
        countiesByState[state.abbreviation] = state.counties;
    }
}

export default function MillList() {
    // const page = usePage<SharedData>();
    const page = usePage<{
        // mills: Mill[];
        states: State[];
        // counties: County[];
        millTypes?: MillType[];
        woodSpecies?: WoodSpecies[];
        millsApiUrl: string;
    }>();
    const pageTitle = 'Mill List';

    // states should always be the same...unless a state doesn't have a particular millType or woodSpecies?
    // map to peel off the counties before shoving into the combobox
    const states: State[] = page.props.states.map((state) => ({id: state.id, name: state.name, abbreviation: state.abbreviation}));
    const countiesByState: {} = page.props.states.map(state => {

    });

    const [selectedState, setSelectedState] = useState<State|null>(null);

    // console.log('page.props.states: ', page.props.states);
    // console.log('states: ', states);

    const [mills, setMills] = useState<Mill[]>([]);
    // const counties = page.props.counties || [];
    // const millTypes = page.props.millTypes || [];
    // const woodSpecies = page.props.woodSpecies || [];

    const handleStateSelectChange = function (stateAbbreviation: Event|string) {
        console.log('in handleStateSelectChange, stateChanged!', stateAbbreviation);
        console.log('update Counties!');
        setSearchParams({state: stateAbbreviation});
        for (const state of states) {
            if (state.abbreviation === stateAbbreviation) {
                setSelectedState(state);
                break;
            }
        };
    }

    const [searchParams, setSearchParams] = useState<Object>({});

    // const handleStateSelectChange: ChangeEventHandler<HTMLSelectElement> = (event) => {
    //     console.log('handleStateSelectChange!', event.target.value);
    // };

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
            setMills(result);
        });
        return () => {
            ignore = true;
        }
    }, []); // empty dependencies make it run on page load

    useEffect(() => {
        console.log('state changed!')
    }, [selectedState]);

    // let mfProps: MillFiltersProps = {
    //     'headline': 'Mill List',
    //     'states': states,
    //     'counties': [],
    //     'millTypes': page.props.millTypes,
    //     'woodSpecies': page.props.woodSpecies,
    //     'onStateSelectChange': handleStateSelectChange,
    // }

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
                    <div className="flex flex-row w-83.75 max-w-83.75">
                        <ul className="flex flex-col justify-evenly items-stretch gap-1">                            
                            {(mills.length > 0) ? mills.map(mill => 
                                <li className="bg-beluga text-black p-8 " key={mill.match_id}>
                                    <h2 className="font-extrabold text-velvet text-lg">
                                        <Link 
                                            href={show(mill.match_id)}
                                            className="hover:underline"
                                        >
                                            {mill.mill_name}
                                        </Link>
                                    </h2>
                                    <address>
                                        {mill.physical_address}
                                        <br />
                                        {mill.physical_address_two}
                                    </address>
                                    <p>
                                        <Link 
                                            href={`https://maps.google.com/?q=${mill.latitude},${mill.longitude}`}
                                            target="_blank"
                                        >
                                            Map This Location
                                        </Link>
                                    </p>
                                    <p><strong>Species: </strong> 
                                        {mill.wood_species ? mill.wood_species.map((wood, index) => {
                                            const prefix = (0 < index) ? ', ' : '';
                                            return prefix + wood.name;
                                        }) : ''}
                                    </p>
                                    <p><strong>Mill Type: </strong> {
                                        mill.mill_types ? mill.mill_types.map((millType, index) => {
                                            const prefix = (0 < index) ? ', ' : '';                                            
                                            return prefix + millType.name;
                                        }) : ''
                                    }</p>
                                </li>
                            ) : (<li className="bg-beluga text-black p-8 min-h-screen w-83.75">Loading...</li>)}
                        </ul>

                    </div>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
