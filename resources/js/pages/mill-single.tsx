import AppLayout from '@/layouts/app-layout';
import { 
    type Mill,
} from '@/types';
import { Head, usePage } from '@inertiajs/react';


export default function MillSingle() {
    const page = usePage<{
        mills: Mill[];
    }>();
    const mills = page.props.mills || [];
    const pageTitle = mills[0] ? mills[0].name + ' | Mill List' : 'Mill List';

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center p-6 text-velvet lg:justify-center lg:p-8">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    {/* <main className="flex w-full max-w-89 flex-col lg:max-w-4xl"> */}
                        
                        <ul className="flex flex-col justify-evenly items-stretch gap-1 w-full max-w-89 lg:max-w-xl">
                            
                            {mills.map(mill => 
                                <li className="bg-beluga text-black p-8 flex" key={mill.match_id}>
                                    <div className="flex flex-col w-1/2">

                                        <h2 className="font-extrabold text-velvet text-lg">{mill.mill_name}</h2>
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
                                    </div>
                                    <div className="flex w-1/2">
                                        <div className="bg-amber-700 w-full">
                                            lil map
                                        </div>
                                    </div>
                                </li>
                            )}
                        </ul>
                    {/* </main> */}
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
