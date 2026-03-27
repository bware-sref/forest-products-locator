// use-gesture-handling.tsx
// wrapper component for leaflet-gesture-handling
// Having wrapper component is allegedly required because of how React Leaflet works.

import { useEffect } from "react";
import { useMap } from "react-leaflet";
import { GestureHandling } from "leaflet-gesture-handling";
// import type { Handler } from "leaflet";
import "leaflet-gesture-handling/dist/leaflet-gesture-handling.css";

// allegedly this will prevent typescript errors related to gestureHandling as a member of Map.
declare module "leaflet" {
    interface Map {
        // original suggestion was giving it the type "any" but IIRC eslint hates any
        // gestureHandling: any;
        gestureHandling: Handler;
    }
}

export const MapGestureHandler = () => {
    // get the map (return whatYouHaveStolenFromMe ;-)
    const map = useMap();

    useEffect(() => {
        // gesture handler to the map
        map.addHandler('gestureHandling', GestureHandling);

        // Enable the handler
        // Blahblah: Typescript might complain
        // yep
        // wow, declare module shut it up
        map.gestureHandling.enable();

        // provide clean up if needed
        return () => {
            map.gestureHandling.disable();
        };
    }, [map]);

    // this component doesn't render anything
    return null;
}