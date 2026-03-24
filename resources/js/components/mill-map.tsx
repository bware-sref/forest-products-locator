// we have to add "use client" to be able to use callbacks with MapLocateControl
// "use client" is also required to use WMS (or so I led myself to believe...)
"use client"

import {
    Mill,
    MillMapProps,
} from '@/types';
import { 
    Map,
    MapCircle,
    MapControlContainer,
    MapLocateControl,
    MapMarker,
    MapPopup,
    // MapSearchControl,
    MapTileLayer,
    // MapWMSTileLayer,
    // MapZoomControl,
} from "@/components/ui/map"
import type { LatLngExpression } from "leaflet";
import { MapPinIcon, Pin, SearchIcon } from "lucide-react";
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from "@/components/ui/input-group";
// import {
//     Combobox,
//     ComboboxContent,
//     ComboboxEmpty,
//     ComboboxInput,
//     ComboboxItem,
//     ComboboxList,
// } from "@/components/ui/combobox";
import { useState } from "react";
import { toast } from "sonner";

// the Leaflet docs keep the ? on the URL :shrug:
// const wmsServer = 'https://www.mrlc.gov/geoserver/NLCD_Canopy/wms?';  // SERVICE=WMS&REQUEST=GetCapabilities
// const wmsLayers = 'default-style-CONUS_Canopy';
// const wmsServer = 'https://wms.gebco.net/mapserv?'; // request=getcapabilities'; //&service=wms&version=1.3.0'
// const wmsLayers = 'GEBCO_LATEST';

// const srefEsri = 'https://esri.sref.info:6443/arcgis/services/SampleWorldCities/MapServer/WMSServer?'; //request=GetCapabilities&service=WMS'
// const srefLayers = '2'; // yep
// const wmsServer = srefEsri;
// const wmsLayers = srefLayers;

/**
 * Currently, the only children we expect would be the mill-filters component.
 * 
 * @param millMapProps
 * @returns 
 */
export default function MillMap({mills, children, ...props}: MillMapProps) {
    const WARNELL_COORDINATES = [33.9439, -83.3769] satisfies LatLngExpression
    const PINS = [
        {
            name: "Warnell School of Forestry and Natural Resources",
            coordinates: WARNELL_COORDINATES,
            icon: <MapPinIcon className="size-6 stroke-velvet" />
        },
    ];
    const [myCoordinates, setMyCoordinates] = useState<LatLngExpression | null>(
        null
    )

    return (
        <Map center={WARNELL_COORDINATES} zoom={5}>
            <MapTileLayer 
            />
{/*
Disable the POC WMS layer until esri.sref.info certificate is fixed.            
            <MapWMSTileLayer 
                url={wmsServer}
                layers={wmsLayers}
            />
*/}            
            <MapCircle 
                center={WARNELL_COORDINATES}
                radius={Math.ceil((100* 5280)/3)}
                className="stroke-velvet"
            />

            {PINS.map((pin) => (
                <MapMarker
                    key={pin.name}
                    position={pin.coordinates}
                    icon={pin.icon}                 
                >
                    <MapPopup className="w-56">{pin.name}</MapPopup>
                </MapMarker>
            ))}

            {/** Mills! */}
            {mills.map((mill) => (
                <MapMarker
                    key={mill.match_id}
                    position={[parseFloat(mill.latitude || '0'), parseFloat(mill.longitude || '0')]}
                    icon={<MapPinIcon className="size-6 stroke-velvet" />}
                >
                    <MapPopup className="w-56">{mill.mill_name}</MapPopup>
                </MapMarker>
            ))}

            <MapLocateControl 
                onLocationFound={(location) =>
                    setMyCoordinates(location.latlng)
                }
                onLocationError={(error) => toast.error(error.message)}
                watch
            />
            {myCoordinates && (
                <MapPopup
                    position={myCoordinates}
                    offset={[0, -5]}
                    className="w-56">
                    {myCoordinates.toString()}
                </MapPopup>
            )}
            <MapControlContainer 
                className="absolute top-5 left-5 z-1000 Xgrid Xgap-1 Xbg-nature Xp-8 Xflex w-full flex-row items-stretch max-w-83.75">
                {/** children are mill-filters */}
                {children}

                {/* <h2 className="text-xl font-bold text-beluga">Mill Map</h2> */}
                {/**
                 * Turns out we can't use the default MapSearchControl for multiple reasons.
                 * The main on is that it won't fn let you style the fn input element, FFnS
                 * Also, the designs don't include a zoom control or locate button.
                 * Also also, dark mode is still enabled for some reason.
                 */}
                {/* <InputGroup className="rounded-2xl bg-beluga dark:bg-beluga">
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
                </InputGroup> */}
                {/* <MapSearchControl
                    id="mapSearchControl"
                    className="static rounded-4xl"
                />
                <MapZoomControl className="static hidden" /> */}
            </MapControlContainer>
        </Map>
    )
}
