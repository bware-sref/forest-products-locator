// we have to add "use client" to be able to use callbacks with MapLocateControl
// "use client" is also required to use WMS (or so I led myself to believe...)
"use client"

import {
    MillListProps,
} from '@/types';
import { 
    Map,
    // MapCircle,
    MapControlContainer,
    // MapLocateControl,
    MapMarker,
    MapMarkerClusterGroup,
    MapPopup,
    MapTileLayer,
} from "@/components/ui/map"
import { MapGestureHandler } from '@/components/extend/map-gesture-handler';
import type { LatLngExpression } from "leaflet";
import {
    MapPinIcon,
} from "lucide-react";
// import { useState } from "react";
// import { toast } from "sonner";
import { Link } from "@inertiajs/react";
import { show } from "@/actions/App/Http/Controllers/MillController"

const zoom = 14;

/**
 * Currently, the only children we expect would be the mill-filters component.
 * In the future, there might be more children, e.g., wmsLayers?
 * 
 * @param millMapProps
 * @returns 
 */
export default function MillSingleMap({mills, children}: MillListProps) {
    const mill = mills[0];
    const mapCenter = [parseFloat(mill.latitude ?? '0'), parseFloat(mill.longitude ?? '0')] satisfies LatLngExpression;

    return (
        <Map 
            className='min-h-[calc(100vh-6rem)] lg:min-h-96'
            center={mapCenter}
            zoom={zoom}
        >
            <MapGestureHandler />
            <MapTileLayer 
            />

            <MapMarkerClusterGroup>
                {/** Mills! */}
                {mills?.map((mill) => (
                    <MapMarker
                        key={mill.match_id}
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
                                <address>
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
                            </div>
                        </MapPopup>
                    </MapMarker>
                ))}
            </MapMarkerClusterGroup>

            <MapControlContainer 
                className="absolute top-5 left-5 z-1000 w-full items-stretch max-w-89">
                {/** children are mill-filters */}
                {children}
            </MapControlContainer>
        </Map>
    )
}
