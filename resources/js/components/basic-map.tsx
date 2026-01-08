import { Map, MapTileLayer } from "@/components/ui/map"
import type { LatLngExpression } from "leaflet"

export function BasicMap() {
    const WARNELL_COORDINATES = [33.9439, -83.3769] satisfies LatLngExpression

    return (
        <Map center={WARNELL_COORDINATES}>
            <MapTileLayer />
        </Map>
    )
}
