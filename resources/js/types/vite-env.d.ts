/// <reference types="vite/client" />

// fix for react-leaflet-markercluster/styles not found
declare module 'react-leaflet-markercluster/styles' {
    const content: unknown;
    export default content;
}