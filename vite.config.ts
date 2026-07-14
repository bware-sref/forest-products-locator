import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import tsConfigPaths from 'vite-tsconfig-paths';
import { ViteImageOptimizer } from 'vite-plugin-image-optimizer';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        tsConfigPaths(),
        ViteImageOptimizer({
            cache: true,
            jpeg: { quality: 60 },
            png: { quality: 80 },
        }),
        wayfinder({
            // this controls the --with-form cli arg
            // however, it fails in github actions, on or off
            formVariants: true,
            // formVariants: false,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    // attempt to resolve the dynamic import warning from Vite
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Force all leaflet-related packages into a single dedicated bundle
                    if (
                        id.includes('node_modules/leaflet') ||
                        id.includes('node_modules/react-leaflet') ||
                        id.includes('node_modules/@react-leaflet') ||
                        id.includes('node_modules/react-leaflet-markercluster')
                    ) {
                        return 'leaflet-vendor';
                    }
                }
            }
        },
    },
});
