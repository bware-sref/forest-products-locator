// we have to add "use client" to be able to use callbacks with MapLocateControl
// "use client" is also required to use WMS (or so I led myself to believe...)
"use client"

import {
    MillMapProps,
} from '@/types';
import { 
    Map,
    MapCircle,
    MapControlContainer,
    MapLocateControl,
    MapMarker,
    MapMarkerClusterGroup,
    MapPopup,
    MapTileLayer,
    // MapWMSTileLayer,
    // MapZoomControl,
} from "@/components/ui/map"
import { MapGestureHandler } from '@/components/extend/map-gesture-handler';
import type { LatLngExpression } from "leaflet";
import {
    MapPinIcon,
} from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Link } from "@inertiajs/react";
import { show } from "@/actions/App/Http/Controllers/MillController"

// the Leaflet docs keep the ? on the URL :shrug:
// const wmsServer = 'https://www.mrlc.gov/geoserver/NLCD_Canopy/wms?';  // SERVICE=WMS&REQUEST=GetCapabilities
// const wmsLayers = 'default-style-CONUS_Canopy';
// const wmsServer = 'https://wms.gebco.net/mapserv?'; // request=getcapabilities'; //&service=wms&version=1.3.0'
// const wmsLayers = 'GEBCO_LATEST';

// const srefEsri = 'https://esri.sref.info:6443/arcgis/services/SampleWorldCities/MapServer/WMSServer?'; //request=GetCapabilities&service=WMS'
// const srefLayers = '2'; // yep
// const wmsServer = srefEsri;
// const wmsLayers = srefLayers;

// centering the map in northern Mississippi should get most mills in frame initially.
// we'll probably need to adjust this.
const MAP_CENTER = [34.887494, -88.873249] satisfies LatLngExpression;

/**
 * Currently, the only children we expect would be the mill-filters component.
 * In the future, there might be more children, e.g., wmsLayers?
 * 
 * @param millMapProps
 * @returns 
 */
export default function MillMap({mills, children, ...props}: MillMapProps) {
    // const WARNELL_COORDINATES = [33.9439, -83.3769] satisfies LatLngExpression
    // const PINS = [
    //     {
    //         name: "Warnell School of Forestry and Natural Resources",
    //         coordinates: WARNELL_COORDINATES,
    //         icon: <MapPinIcon className="size-6 stroke-velvet" />
    //     },
    // ];
    const [myCoordinates, setMyCoordinates] = useState<LatLngExpression | null>(
        null
    )

    return (
        <Map center={MAP_CENTER} zoom={5}>
            <MapGestureHandler />
            <MapTileLayer 
            />
{/*
Disable the POC WMS layer until esri.sref.info certificate is fixed.            
            <MapWMSTileLayer 
                url={wmsServer}
                layers={wmsLayers}
            />
*/}

            {/* {PINS.map((pin) => (
                <MapMarker
                    key={pin.name}
                    position={pin.coordinates}
                    icon={pin.icon}                 
                >
                    <MapPopup className="w-72">{pin.name}</MapPopup>
                </MapMarker>
            ))} */}

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
            <MapLocateControl 
                onLocationFound={(location) =>
                    setMyCoordinates(location.latlng)
                }
                onLocationError={(error) => toast.error(error.message)}
                watch
            />
            {/** MapCircle should only display after the user has clicked the locator button */}
            {myCoordinates && (
                <>
                    <MapCircle 
                        center={myCoordinates}
                        radius={Math.ceil((100* 5280)/3)}
                        className="stroke-velvet"
                    />
                    <MapPopup
                        position={myCoordinates}
                        offset={[0, -5]}
                        className="w-56">
                        {myCoordinates.toString()}
                    </MapPopup>
                </>
            )}
            <MapControlContainer 
                className="absolute top-5 left-5 z-1000 w-full items-stretch max-w-83.75">
                {/** children are mill-filters */}
                {children}
            </MapControlContainer>
        </Map>
    )
}
