// we have to add "use client" to be able to use callbacks with MapLocateControl
// "use client" is also required to use WMS (or so I led myself to believe...)
"use client"

import { useEffect, type ReactNode } from 'react';
import {
    MillListProps,
} from '@/types';
import {
    Map as MapContainer,
    MapCircle,
    MapControlContainer,
    MapMarker,
    MapMarkerClusterGroup,
    MapTileLayer,
    // MapWMSTileLayer,
} from "@/components/ui/map"
import { MapGestureHandler } from '@/components/extend/map-gesture-handler';
import type { Map as LeafletMap, LatLngExpression } from "leaflet";
import { useMap } from "react-leaflet";
import {
    CircleIcon,
} from "lucide-react";
import MillMapMarker from '@/components/mill-map-marker';
import {
    mapDefaults
} from "@/config/map-defaults";


/**
 * useMap() is only available in children of MapContainer, so this bridges
 * the map instance back out to the caller via onMapReady.
 */
function MapReadyBridge({ onMapReady }: { onMapReady: (map: LeafletMap) => void }) {
    const map = useMap();

    useEffect(() => {
        onMapReady(map);
    }, [map, onMapReady]);

    return null;
}


// the Leaflet docs keep the ? on the URL :shrug:
// const wmsServer = 'https://www.mrlc.gov/geoserver/NLCD_Canopy/wms?';  // SERVICE=WMS&REQUEST=GetCapabilities
// const wmsLayers = 'default-style-CONUS_Canopy';
// const wmsServer = 'https://wms.gebco.net/mapserv?'; // request=getcapabilities'; //&service=wms&version=1.3.0'
// const wmsLayers = 'GEBCO_LATEST';

// const srefEsri = 'https://esri.sref.info:6443/arcgis/services/SampleWorldCities/MapServer/WMSServer?'; //request=GetCapabilities&service=WMS'
// const srefLayers = '2'; // yep
// const wmsServer = srefEsri;
// const wmsLayers = srefLayers;

// default map configs moved to resources/js/config/map-defaults.tsx

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
    onMapReady,
    notice = null,
    mapConfigs,
}: MillListProps & {
    /** message shown over the map, e.g. when no mills match the filters */
    notice?: ReactNode;
}) {

    // parse radius as a float, handling the empty case with '0'
    const filterRadius = parseFloat(radius ?? '0');    

    const mapCenter = (mapConfigs && mapConfigs.center) ? 
        [mapConfigs.center.latitude, mapConfigs.center.longitude] as LatLngExpression :
        [mapDefaults.center.latitude, mapDefaults.center.longitude] as LatLngExpression; //MAP_CENTER;
    const mapZoom = mapConfigs?.zoom.multiple ?? mapDefaults.zoom.multiple;
    const tileAttribution = mapConfigs?.tile.attribution.full ?? mapDefaults.tile.attribution.full;
    const tileUrl = mapConfigs?.tile.url ?? mapDefaults.tile.url;
    const showPolygonsOnHover = mapConfigs?.cluster.polygon.hover ?? mapDefaults.cluster.polygon.hover;

    return (
        <MapContainer
            className="min-h-[calc(100vh-6rem)] lg:min-h-[calc(100vh-4rem)]"
            center={mapCenter}
            zoom={mapZoom}
        >
            {onMapReady && <MapReadyBridge onMapReady={onMapReady} />}
            <MapGestureHandler data-thing="map-gesture-handler" />
            <MapTileLayer
                data-thing="map-tile-layer"
                attribution={tileAttribution}
                url={tileUrl}
            />
{/*
Disable the POC WMS layer until esri.sref.info certificate is fixed.            
            <MapWMSTileLayer 
                url={wmsServer}
                layers={wmsLayers}
            />
*/}

            <MapMarkerClusterGroup 
                data-thing="map-marker-cluster-group"
                showCoverageOnHover={showPolygonsOnHover}
            >
                {/** Mills! */}
                {mills?.map((mill) => (
                    // hoisted key to this level to avoid warning about all list items should have keys
                    // weird that it being assigned in the MillMapMarker component didn't seem to matter
                    <MillMapMarker 
                        key={mill.match_id}
                        mill={mill}/>
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
                    key="user-position"
                    position={coordinates}
                    // just a visual marker for the search center: keep it out of the Tab order
                    keyboard={false}
                    interactive={false}
                    icon={
                        <CircleIcon
                            size={10}
                            className="fill-green-700"
                        />
                    }
                />
            )}
            {children}
            {notice && (
                <MapControlContainer className="top-4 left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-md">
                    {notice}
                </MapControlContainer>
            )}
        </MapContainer>
    )
}
