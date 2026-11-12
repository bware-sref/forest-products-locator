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
    // no coordinates yet (not geocoded): leave it off the map rather than pinning it at 0, 0
    if (mill.latitude == null || mill.longitude == null) {
        return null;
    }

    return (
        <MapMarker
            // key={mill.match_id}
            position={[mill.latitude, mill.longitude]}
            // add method to map mill.mill_type[0] to the appropriate icon
            icon={<MapPinIcon className="size-6 stroke-velvet" />}
            // the marker is a focusable button; this is what screen readers announce
            title={mill.mill_name}
        >
            <MapPopup className="w-72">
                <div className="flex flex-col text-left text-velvet text-[16px]">
                    <h3 className="font-extrabold text-lg mb-5">{mill.mill_name}</h3>
                    <address className="not-italic mb-5">
                        {mill.physical_address}
                        <br />
                        {mill.physical_address_two}
                    </address>

                    {mill.wood_species?.length ? (
                        <p>
                            <strong>Species: </strong>
                            {mill.wood_species.map((wood, index) => {
                                const prefix = (0 < index) ? ', ' : '';
                                return prefix + wood.name;
                            })}
                        </p>
                    ) : '' }

                    {mill.mill_types?.length ? (
                        <p>
                            <strong>Mill Type: </strong>
                            {mill.mill_types.map((millType, index) => {
                                const prefix = (0 < index) ? ', ' : '';                                            
                                return prefix + millType.name;
                            })}
                        </p>
                    ) : ''}


                    <div className="pt-5">
                        <Link
                            viewTransition
                            href={show(mill.match_id)}
                            className="underline hover:no-underline"
                            target="_blank"
                        >
                            More Information...
                        </Link>
                    </div>
                    {children}
                </div>
            </MapPopup>
        </MapMarker>        
    );
}