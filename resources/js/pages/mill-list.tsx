import AppLayout from '@/layouts/app-layout';
import { 
    // type SharedData,
    type County,
    type Mill,
    type MillType,
    type State,
    type WoodSpecies
} from '@/types';
import { 
    Head,
    Link,
    usePage
} from '@inertiajs/react';
// import { Link } from 'lucide-react';
import { show } from '@/actions/App/Http/Controllers/MillController';

export default function MillList() {
    // const page = usePage<SharedData>();
    const page = usePage<{
        mills: Mill[];
        states: State[];
        counties: County[];
        millTypes?: MillType[];
        woodSpecies?: WoodSpecies[];
    }>();
    const pageTitle = 'Mill List';
    const mills = page.props.mills || [];
    const states = page.props.states || [];
    const counties = page.props.counties || [];
    const millTypes = page.props.millTypes || [];
    const woodSpecies = page.props.woodSpecies || [];


    // console.log('mills[0]: ', mills[0]);
    // console.log('states[0]: ', states[0] || 'no state[0]');
    // console.log('counties[0]: ', counties[0] || 'no counties');
    // console.log('millTypes: ', millTypes);
    // console.log('woodSpecies: ', woodSpecies);

    // console.log('mills[0].wood_species', mills[0].wood_species || 'no wood_species?');
    // console.log('mills[0].mill_types', mills[0].mill_types || 'no mill_types?');

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center Xbg-nature p-6 text-velvet lg:justify-center lg:p-8 Xdark:bg-nature">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col lg:max-w-4xl ffslg:flex-row">
                        <div className="flex w-full flex-row">filters</div>
                        <ul className="flex flex-col justify-evenly items-stretch gap-1 max-w-[335px] lg:max-w-xl">
                            
                            {mills.map(mill => 
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
                                    <p>Map This Location</p>
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
                            )}
                        </ul>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
