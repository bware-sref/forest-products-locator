import {
    ReactNode,
} from "react";
import {
    MapMarker,
    MapPopup
} from "@/components/ui/map";
import {
    MapPinIcon,
} from "lucide-react";
import {
    Link
} from "@inertiajs/react";
import {
    show
} from "@/actions/App/Http/Controllers/MillController";
import {
    Mill
} from "@/types";

/**
 * Requires a Mill
 * Accepts children
 * Should maybe also spread props?
 * @returns 
 */
export default function MillMapMarker({
    mill,
    children,
}: { 
    mill: Mill;
    children?: ReactNode;
}) {
    return (
        <MapMarker
            // key={mill.match_id}
            position={[
                parseFloat(mill.latitude || '0'),
                parseFloat(mill.longitude || '0')
            ]}
            // add method to map mill.mill_type[0] to the appropriate icon
            icon={<MapPinIcon className="size-6 stroke-velvet" />}
        >
            <MapPopup className="w-72">
                <div className="flex flex-col text-left text-velvet text-[16px]">
                    <h3 className="font-extrabold text-lg">{mill.mill_name}</h3>
                    <address className="not-italic">
                        {mill.physical_address}
                        <br />
                        {mill.physical_address_two}
                    </address>
                    <p>
                        <strong>Species: </strong>
                        {mill.wood_species?.map((wood, index) => {
                            const prefix = (0 < index) ? ', ' : '';
                            return prefix + wood.name;
                        })}
                    </p>
                    <p>
                        <strong>Mill Type: </strong>
                        {mill.mill_types?.map((millType, index) => {
                            const prefix = (0 < index) ? ', ' : '';                                            
                            return prefix + millType.name;
                        })}
                    </p>                                                    
                    <p>
                        <Link
                            viewTransition
                            href={show(mill.match_id)}
                            className="underline hover:no-underline"
                            target="_blank"
                        >
                            More Information...
                        </Link>
                    </p>
                    {children}
                </div>
            </MapPopup>
        </MapMarker>        
    );
}