// we have to add "use client" to be able to use callbacks with MapLocateControl
// "use client" is also required to use WMS (or so I led myself to believe...)
"use client"

import {
    MillListProps,
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
import { LatLngExpression } from "leaflet";
import {
    MapPinIcon,
    SlidersHorizontalIcon,
} from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Link } from "@inertiajs/react";
import { show } from "@/actions/App/Http/Controllers/MillController";
import { Button } from "@/components/ui/button"
import {
    DialogDrawer,
} from "@/components/extend/dialog-drawer";
import { DialogProps } from 'vaul';
// import { cn } from '@/lib/utils';

// import { useMap, useMapEvents } from "react-leaflet/hooks";
// import { locate } from "@/lib/locate";

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
export default function MillMap({mills, children}: MillListProps) {
    const [myCoordinates, setMyCoordinates] = useState<LatLngExpression | null>(
        null
    );
    /**
     * Monitoring drawer open state allows us to prevent the "blocked aria-hidden on element because child has focus" issue.
     */
    const [drawerOpen, setDrawerOpen] = useState<boolean>(false);

    const drawerProps = {
        direction: "left",
        modal: true,
        container: document.getElementById('map-control-container'),
        onOpenChange: setDrawerOpen,
        autoFocus: drawerOpen,
        className: 'w-screen min-w-full max-w-full bg-green-500'
    } as DialogProps;

    const dialogProps = {
        modal: true,
        container: document.getElementById('map-control-container'),
        onOpenChange: setDrawerOpen,
        autoFocus: drawerOpen
    } as DialogProps;

    /**
     * make this a component
     */
    const triggerButton = (
        <Button
            className="bg-coupe border border-beluga text-beluga text-[16px] font-bold justify-self-end ml-auto rounded-sm z-100"
            id="filter-trigger"
        >
            Filters
            <SlidersHorizontalIcon
                data-icon="inline-end"                            
                className="w-6 h-6 ml-2 size-1"
            />
        </Button>
    );

    return (
        <Map 
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
                {/**
                 * Make MillMapMarker component
                 */}
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

            {/* <MapLocateControl 
                onLocationFound={(location) =>
                    setMyCoordinates(location.latlng)
                }
                onLocationError={(error) => toast.error(error.message)}
                watch
            /> */}

            {/** MapCircle should only display after the user has clicked the locator button */}
            {myCoordinates && (
                <>
                    {/* <MapCircle 
                        center={myCoordinates}
                        radius={Math.ceil((100* 5280)/3)}
                        className="stroke-velvet"
                    /> */}
                    <MapPopup
                        position={myCoordinates}
                        offset={[0, -5]}
                        className="w-56">
                        {myCoordinates.toString()}
                    </MapPopup>
                </>
            )}

            {/**
             * Put the map controls in a DialogDrawer so we can hide them instead of covering everything.
             * Dialog displays on large screens.
             * Drawer displays on small screens.
             */}
            <MapControlContainer
                data-thing="map-control-container"
                className="relative top-0 left-0 z-1000 flex flex-wrap bg-lorne w-full"
                id="map-control-container"
            >
                {/**
                 * It might be possible to externalize the map controls.
                 */}                
                <div className="w-full lg:max-w-7xl mx-auto flex flex-row justify-between px-6 py-2">
                    <h1 className="font-extrabold text-3xl text-beluga">Mill Map</h1>
                    <DialogDrawer
                        trigger={triggerButton}
                        title="Mill Filters"
                        description="Filter mills based on the criteria below."
                        drawerContentProps={{
                            className: "bg-red-500 lg:bg-lorne z-100 border-r-lorne w-full max-w-screen p-0",                            
                        }}
                        drawerHeaderProps={{
                            className: "sr-only"
                        }}
                        drawerProps={drawerProps}
                        dialogHeaderProps={{
                            className: "sr-only"
                        }}
                        dialogContentProps={{
                            className: "bg-nature lg:bg-lorne z-100 border-lorne",                            
                        }}
                        dialogProps={dialogProps}
                    >
                        {children}
                    </DialogDrawer>

                    {/**
                     * This is the version that's always a drawer.
                     */}
                    {/* <Drawer 
                        direction="left"
                        modal={true}
                        container={document.getElementById('map-control-container')}
                        onOpenChange={setDrawerOpen}
                        autoFocus={drawerOpen}
                    >
                        <DrawerTrigger asChild>
                            <Button
                                className="bg-coupe border border-beluga text-beluga text-[16px] font-bold justify-self-end ml-auto rounded-sm XClg:rotate-90 origin-bottom-left Xlg:-translate-y-full Xtop-0 z-100"
                                id="filter-trigger"
                            >
                                Filters
                                <SlidersHorizontalIcon
                                    data-icon="inline-end"                            
                                    className="w-6 h-6 ml-2 size-1"
                                />
                            </Button>
                        </DrawerTrigger>
                        <DrawerContent data-thing="drawer-content" className="bg-nature lg:bg-lorne z-100 border-r-lorne">
                            <DrawerHeader className="sr-only">
                                <DrawerTitle>Filter Mills</DrawerTitle>
                                <DrawerDescription>Filter mills based on the criteria below.</DrawerDescription>
                            </DrawerHeader>
                            {children}
                        </DrawerContent>
                    </Drawer> */}
                </div>
                {/** here is where our dropdown trigger goes */}
            </MapControlContainer>

            {/**
             * This is the "original" filter design, which displays in front of the map at all times.
             */}
            {/* <MapControlContainer 
                data-thing="map-control-container"
                className="absolute top-0 lg:top-5 lg:left-5 z-1000 w-full items-stretch max-w-screen lg:max-w-90 bg-nature lg:bg-lorne px-4">
                {/** children are mill-filters * /}
                {children}
            </MapControlContainer> */}
        </Map>
    )
}
