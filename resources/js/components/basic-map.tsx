// we have to add "use client" to be able to use callbacks with MapLocateControl
// "use client" is also required to use WMS (or so I led myself to believe...)
"use client"

import { 
    Map,
    MapLocateControl,
    MapMarker,
    MapPopup,
    MapTileLayer
} from "@/components/ui/map"
import type { LatLngExpression } from "leaflet"
import { useState } from "react"
import { toast } from "sonner"

export function BasicMap() {
    const WARNELL_COORDINATES = [33.9439, -83.3769] satisfies LatLngExpression
    const PINS = [
        {
            name: "Warnell School of Forestry and Natural Resources",
            coordinates: WARNELL_COORDINATES,
        },
    ];
    const [myCoordinates, setMyCoordinates] = useState<LatLngExpression | null>(
        null
    )

    return (
        <Map center={WARNELL_COORDINATES}>
            <MapTileLayer />
            {PINS.map((pin) => (
                <MapMarker key={pin.name} position={pin.coordinates}>
                    <MapPopup className="w-56">{pin.name}</MapPopup>
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
        </Map>
    )
}
