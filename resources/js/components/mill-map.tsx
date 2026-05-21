// we have to add "use client" to be able to use callbacks with MapLocateControl
// "use client" is also required to use WMS (or so I led myself to believe...)
"use client"

import {
    MillListProps,
} from '@/types';
import { 
    Map as MapContainer,
    MapCircle,
    MapMarker,
    MapMarkerClusterGroup,
    MapTileLayer,
    // MapWMSTileLayer,
} from "@/components/ui/map"
import { MapGestureHandler } from '@/components/extend/map-gesture-handler';
import { LatLngExpression } from "leaflet";
import {
    CircleIcon,
} from "lucide-react";
import MillMapMarker from '@/components/mill-map-marker';


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
const MAP_CENTER = [34.887494, -88.873249] satisfies LatLngExpression;


/**
 * Currently, the only children we expect would be the mill-filters component.
 * In the future, there might be more children, e.g., wmsLayers?
 * 
 * @param millMapProps
 * @returns 
 */
export default function MillMap({
    mills,
    children,
    coordinates = null,
    radius = null,
}: MillListProps) {

    // parse radius as a float, handling the empty case with '0'
    const filterRadius = parseFloat(radius ?? '0');

    return (
        <MapContainer 
            className="min-h-[calc(100vh-6rem)] lg:min-h-[calc(100vh-4rem)]"
            center={MAP_CENTER}
            zoom={5}
        >
            <MapGestureHandler data-thing="map-gesture-handler" />
            <MapTileLayer 
                data-thing="map-tile-layer"
            />
{/*
Disable the POC WMS layer until esri.sref.info certificate is fixed.            
            <MapWMSTileLayer 
                url={wmsServer}
                layers={wmsLayers}
            />
*/}

            <MapMarkerClusterGroup data-thing="map-marker-cluster-group">
                {/** Mills! */}
                {mills?.map((mill) => (
                    <MillMapMarker mill={mill}/>
                ))}
            </MapMarkerClusterGroup>

            {/** MapCircle should only display after the user has clicked the locator button */}
            {coordinates && filterRadius > 0 ? (
                <MapCircle 
                    center={coordinates}
                    radius={Math.ceil(( filterRadius * 5280)/3)}
                    className="stroke-velvet"
                />) : null }
            {coordinates && (
                <MapMarker
                    position={coordinates}
                    icon={
                        <CircleIcon
                            size={10}
                            className="fill-green-700"
                        />
                    }
                />
            )}
            {children}
        </MapContainer>
    )
}
