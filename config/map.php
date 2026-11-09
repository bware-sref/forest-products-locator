<?php
/** 
 * configs for the mill map
 */


return [
    'center' => [
        'latitude' => env('MAP_CENTER_LAT', 34.887494),
        'longitude' => env('MAP_CENTER_LNG', -88.873249),
    ],
    'cluster' => [
        // polygons
        'polygon' => [
            'hover' => env('MAP_CLUSTER_POLYGON_HOVER', false),
        ],
    ],
    'tile' => [
        'attribution' => [
            'url' => env('MAP_TILE_ATTRIBUTION_URL', 'https://www.openstreetmap.org/copyright'),
            'text' => env('MAP_TILE_ATTRIBUTION_TEXT', 'OpenStreetMap'),
            'full' => env('MAP_TILE_ATTRIBUTION_FULL', '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>'),
        ],
        'url' => env('MAP_TILE_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
    ],
    /**
     * Default zoom level for map types
     * multiple is the mill map
     * single is an individual mill page
     * larger values === tighter zoom
     */
    'zoom' => [
        'multiple' => env('MAP_ZOOM_MULTIPLE', 5),
        'single' => env('MAP_ZOOM_SINGLE', 14),
    ],
];