import AppLayout from '@/layouts/app-layout';
import { 
    // type SharedData,
    type Mill,
} from '@/types';
import { Head, usePage } from '@inertiajs/react';
// import { Head } from '@inertiajs/react';

export default function MillList() {
    // const page = usePage<SharedData>();
    const page = usePage<{
        mills: Mill[];
    }>();
    const pageTitle = 'Mill List';
    const mills = page.props.mills || [];


    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center Xbg-nature p-6 text-velvet lg:justify-center lg:p-8 Xdark:bg-nature">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                        <ul className="flex flex-col justify-evenly items-stretch gap-1">
                            
                            {mills.map(mill => 
                                <li className="bg-beluga text-black p-8 " key={mill.match_id}>
                                    <h2 className="font-extrabold text-velvet text-lg">{mill.mill_name}</h2>
                                    <address>
                                       {mill.physical_address}
                                       <br />
                                       {mill.physical_address_two}
                                    </address>
                                    <p>Map This Location</p>
                                    <p><strong>Species:</strong> {mill.species}</p>
                                    <p><strong>Mill Type:</strong> {mill.type}</p>
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
