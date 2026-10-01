import {
    useEffect,
    useState,
} from "react";
// import L from "leaflet"

export function useLeaflet() {
    const [L, setL] = useState<typeof import("leaflet") | null>(null)
    const [LeafletDraw, setLeafletDraw] = useState<
        typeof import("leaflet-draw") | null
    >(null)

    useEffect(() => {
        if (L && LeafletDraw) return
        if (typeof window !== "undefined") {
            if (!L) {
                // default, nextJS version
                // setL(require("leaflet"))
                // React version
                import("leaflet").then((module) => {
                    setL(module)
                })
            }
            if (!LeafletDraw) {
                // default, nextJS version
                // setLeafletDraw(require("leaflet-draw"))
                // React version
                import("leaflet-draw").then((module) => {
                    setLeafletDraw(module)
                })
            }
        }
    }, [L, LeafletDraw])

    return { L, LeafletDraw }
}