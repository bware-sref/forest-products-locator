import { Map, MapMarker, MapTileLayer } from "@/components/ui/map"
import type { LatLngExpression } from "leaflet"

export function BasicMap() {
    const WARNELL_COORDINATES = [33.9439, -83.3769] satisfies LatLngExpression
    const PINS = [
        {
            name: "Warnell School of Forestry and Natural Resources",
            coordinates: WARNELL_COORDINATES,
        },
    ];

    return (
        <Map center={WARNELL_COORDINATES}>
            <MapTileLayer />
            {PINS.map((pin) => (
                <MapMarker key={pin.name} position={pin.coordinates} />
            ))}
        </Map>
    )
}
