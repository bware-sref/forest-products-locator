import AppLayout from '@/layouts/app-layout';
import { 
    // type SharedData,
    // type County,
    type Mill,
    type MillType,
    type State,
    type WoodSpecies
} from '@/types';
import { 
    Head,
    Link,
    usePage,
} from '@inertiajs/react';
import { 
    ChangeEventHandler,
    useEffect,
    useState,    
} from 'react';
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from "@/components/ui/input-group";
import {
    Combobox,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
} from "@/components/ui/combobox";
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

    console.log('page.props.states: ', page.props.states);
    console.log('states: ', states);

    // const [mills, setMills] = useState(page.props.mills || []);
    const [mills, setMills] = useState<Mill[]>([]);
    // const counties = page.props.counties || [];
    // const millTypes = page.props.millTypes || [];
    // const woodSpecies = page.props.woodSpecies || [];



    const [searchParams, setSearchParams] = useState<Object>({});

    // const handleStateSelectChange: ChangeEventHandler<HTMLSelectElement> = (event) => {
    //     console.log('handleStateSelectChange!', event.target.value);
    // };

    /**
     * Here's where we fetch the mill data on page load!
     */
    useEffect(() => {
        // react docs recommend using an ignore flag to prevent useEffect running twice
        /**
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

                    <div className="flex w-full flex-row items-stretch max-w-83.75">
                        <div className="grid gap-1 bg-nature p-8 w-full">
                            <h2 className="text-xl font-bold text-beluga pb-2">Mill List</h2>
                            {/**
                             * mess
                             */}
                            <FieldGroup>
                                <Field>
                                    <InputGroup className="rounded-2xl bg-beluga dark:bg-beluga">
                                        <InputGroupInput 
                                            id="searchMills"
                                            className="text-velvet dark:text-velvet"
                                            placeholder="Search mills..."
                                        />
                                        <InputGroupAddon align="inline-end">
                                            <InputGroupButton                            
                                                aria-label="Search"
                                                title="Search"
                                                size="icon-sm"
                                            >
                                                <SearchIcon />
                                            </InputGroupButton>
                                        </InputGroupAddon>
                                    </InputGroup>
                                </Field>
                                <Field>
                                    <FieldLabel className="text-white">State:</FieldLabel>                                    
                                    <Combobox
                                        items={states}
                                        itemToStringLabel={(state: State) => state.name}
                                        itemToStringValue={(state: State) => state.abbreviation}
                                        defaultValue={null}
                                    >
                                        <ComboboxInput 
                                            placeholder="Select a state" 
                                            className="bg-beluga text-velvet"
                                        />
                                        <ComboboxContent className="bg-beluga text-velvet">
                                            <ComboboxEmpty>When would this happen?</ComboboxEmpty>
                                            <ComboboxList className="bg-beluga text-velvet">
                                                {(state) => (
                                                    <ComboboxItem
                                                        key={state.abbreviation}
                                                        value={state}
                                                    >
                                                        {state.name}
                                                    </ComboboxItem>
                                                )}
                                            </ComboboxList>
                                        </ComboboxContent>

                                    {/* <select id="stateSelector">
                                        <option value="">Select a State</option>
                                        {(states.length < 1) ? '' : states.map(state => 
                                        <option key={state.abbreviation} value={state.abbreviation}>{state.name}</option>
                                        )}
                                    </select> */}
                                    </Combobox>
                                </Field>
                            </FieldGroup>
                            <div>
                            </div>
                        </div>                            
                    </div>
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
