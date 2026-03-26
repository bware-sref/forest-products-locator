import { 
    // type County,
    // weird, VSCode doesn't consider Mill type to be used.
    // type Mill,
    type MillListProps,
    // type MillType,
    // type State,
    // type WoodSpecies,
} from '@/types';
import { 
    Link,
} from '@inertiajs/react';

import { show } from '@/actions/App/Http/Controllers/MillController';


/**
 * Maybe we want to allow children to inform the no mills state?
 */
export default function MillList({mills, children, ...props}: MillListProps) {

    return (
        <div className="flex flex-row w-83.75 max-w-83.75" {...props}>
            <ul className="flex flex-col justify-evenly items-stretch gap-1">                            
                {(mills && mills.length > 0) ? mills.map(mill => 
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
                {children}
            </ul>

        </div>
    );
}