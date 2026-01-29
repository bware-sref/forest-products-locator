// we have to add "use client" to be able to use callbacks with MapLocateControl
// "use client" is also required to use WMS (or so I led myself to believe...)
"use client"

import { 
    Map,
    MapCircle,
    MapControlContainer,
    MapLocateControl,
    MapMarker,
    MapPopup,
    MapSearchControl,
    MapTileLayer,
    MapZoomControl,
} from "@/components/ui/map"
import type { LatLngExpression } from "leaflet"
import { MapPinIcon } from "lucide-react";
import { useState } from "react"
import { toast } from "sonner"

export function ComplexMap() {
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
                className="absolute top-5 left-5 z-1000 grid gap-1 bg-beluga">
                <MapSearchControl className="static" />                                       
                <MapZoomControl className="static" />
            </MapControlContainer>
        </Map>
    )
}
