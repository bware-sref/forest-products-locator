<?php
/**
 * config/arcgis.php
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | The Laravel Storage disk used when an endpoint does not specify its own.
    | Typical values: 'local', 's3', 'sftp'
    |
    */

    'default_disk' => env('ARCGIS_DEFAULT_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for a response from the ArcGIS REST API before aborting.
    |
    */

    'timeout' => env('ARCGIS_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Feature Service Endpoints
    |--------------------------------------------------------------------------
    |
    | Each key is a short handle used by the Artisan command and anywhere else
    | you need to reference an endpoint by name.
    |
    | Required per entry:
    |   url  — Full FeatureServer layer URL (no trailing slash).
    |
    | Optional per entry:
    |   disk        — Override the default Storage disk for this endpoint.
    |   geojson     — Storage path for the GeoJSON export.
    |   csv         — Storage path for the CSV export.
    |   description — Human-readable label shown in command output.
    |
    */

    /**
     * I'm not sure if I like the URLs being defined in the configs.
     * Also, allowing different names for each file type and endpoint seems useful at first.
     * But things like this should really use timestamp prefixed filenames.
     * I.e., each endpoint should provide a base file name (or hell, just use the fucking endpoint key).
     * The base file name gets plugged into the overall filename pattern.
     * The overall filename pattern gets prefixed with a date.
     * 
     * Also, the default parameters assumed by the service are insufficient for all states.
     * Therefore, if we put all this crap in configs, we at least need to account for things like
     *  'orderByFields' => 'OBJECTID', // MOST STATES
     *  'orderByFields' => 'FID', // Louisiana
     * 
     * What else?
     * Oh yeah, the Python version didn't do null checks so we probably need to check in the service.
     */

    'export_path' => env('ARCGIS_EXPORT_PATH', 'arcgis'),
    /**
     * or should it be endpoint.slug (or just slug)?
     */
    'file_name_pattern' => env('ARCGIS_FILE_NAME_PATTERN', ':export_path:'.DIRECTORY_SEPARATOR.':timestamp:__:slug:.:extension:'),
    'timestamp_format' => env('ARCGIS_TIMESTAMP_FORMAT', 'Y-m-d\TH-i-s'),


    'endpoints' => [

        /**
         * Alabama
         */
        'al' => [
            'url' => env('ARCGIS_AL_URL', 'https://gis.forestry.alabama.gov/arcgis/rest/services/AFCEnterprise/ForestIndustryDirectory/MapServer/0'),
            'description' => env('ARCGIS_AL_DESCRIPTION', 'Alabama Forest Industry Directory'),
            'slug' => env('ARCGIS_AL_SLUG', 'alabama'),
            /**
             * There's probably a better way to handle a possible array of params
             * Gemini suggests JSON in .env and json_decode($val, true) here.
             * Let's give it a whirl...
             * BTW, where values must be wrapped in single quotes or it fails  VVVVVVVVVVV
             */
            'params' => json_decode(env('ARCGIS_AL_PARAMS', '{"where": "PriSec=\'Primary\'"}'), true),
        ],

        /**
         * Arkansas?
         * Nope!
         */
        // 'ar' => [
        //     'url' => env('ARCGIS_AR_URL', ''),
            // 'description' => 'Arkansas Mills',
        //     'slug' => 'arkansas',
        //     'params' => ['where' => "Physical_S='AR'"]
        // ],

        /**
         * Florida?
         * Nope!
         */
        // 'fl' => [
        //     'url' => env('ARCGIS_FL_URL', ''),
            // 'description' => 'Florida Mills',        
        //     'slug' => 'florida',
        // ],

        /**
         * Georgia
         * I'm torn between using the full, kebab-case name vs. state postal abbreviations.
         * The latter are only 2 letters
         */
        'ga' => [
            'url'         => env('ARCGIS_GA_URL', 'https://services2.arcgis.com/iXA1dC6ldRMKRwra/arcgis/rest/services/Primary_Wood_Using_Industries_of_Georgia__Public_View/FeatureServer/0'),
            'description' => env('ARCGIS_GA_DESCRIPTION', 'Primary Wood Using Industries of Georgia'),
            'slug' => env('ARCGIS_GA_SLUG', 'georgia'),
            // 'disk'        => env('ARCGIS_GA_DISK', null),   // falls back to default_disk
            // 'geojson'     => env('ARCGIS_GA_GEOJSON', 'arcgis/georgia_mills.geojson'),
            // 'csv'         => env('ARCGIS_GA_CSV',     'arcgis/georgia_mills.csv'),
        ],

        /**
         * Kentucky?
         */
        // 'ky' => [
        //     'url' => env('ARCGIS_KY_URL', ''),
        //     'description' => 'Kentucky Mills',
        //     'slug' => 'kentucky',
        // ],

        /**
         * Louisiana
         */
        'la' => [
            'url' => env('ARCGIS_LA_URL', 'https://services2.arcgis.com/njxlOVQKvDzk10uN/arcgis/rest/services/Location_of_Mills/FeatureServer/0'),
            'description' => env('ARCGIS_LA_DESCRIPTION', 'Lousiana Location of Mills'),
            'slug' => env('ARCGIS_LA_SLUG', 'louisiana'),
            /**
             * Gemini suggested JSON for handling arrays in .env
             */
            'params' => json_decode(env('ARCGIS_LA_PARAMS', '{"orderByFields": "FID"}'), true),
        ],

        /**
         * Mississippi?
         */
        // 'ms' => [
        //     'url' => env('ARCGIS_MS_URL', ''),
        //     'description' => env('ARCGIS_MS_DESCRIPTION', 'Mississippi Mills'),
        //     'slug' => env('ARCGIS_NC_SLUG', 'mississippi'),
        //     'params' => json_decode(env('ARCGIS_MS_PARAMS', '{}'), true),
        // ],

        /**
         * North Carolina
         */
        'nc' => [
            'url'         => env('ARCGIS_NC_URL', 'https://services6.arcgis.com/3Sj1fiIDvQtFfbvg/arcgis/rest/services/Primary_Processors_Website_Map_Updated_4_23_/FeatureServer/1'),
            'description' => env('ARCGIS_NC_DESCRIPTION', 'North Carolina Primary Processors Website Map'),
            'slug' => env('ARCGIS_NC_SLUG', 'north-carolina'),
            'params' => json_decode(env('ARCGIS_NC_PARAMS', '{}'), true),
            // 'disk'        => env('ARCGIS_NC_DISK', null),
            // 'geojson'     => env('ARCGIS_NC_GEOJSON', 'arcgis/north-carolina-mills.geojson'),
            // 'csv'         => env('ARCGIS_NC_CSV',     'arcgis/north-carolina-mills.csv'),
        ],

        /**
         * Oklahoma!
         */
        'ok' => [
            'url' => env('ARCGIS_OK_URL', 'https://services3.arcgis.com/yrIZ0Nv0mSGTWJsH/arcgis/rest/services/OK_Sawmill_Locations_view/FeatureServer/0'),
            'description' => env('ARCGIS_OK_DESCRIPTION', 'Oklahoma Sawmill Locations'),
            'slug' => env('ARCGIS_OK_SLUG', 'oklahoma'),
            'params' => json_decode(env('ARCGIS_OK_PARAMS', '{}'), true),
        ],

        /**
         * South Carolina
         */
        'sc' => [
            'url' => env('ARCGIS_SC_URL', 'https://services9.arcgis.com/MvVEDRWecTZBD2xl/arcgis/rest/services/PrimaryMills_SC_view/FeatureServer/0'),
            'description' => env('ARCGIS_SC_DESCRIPTION', 'Primary Mills of South Carolina'),
            'slug' => env('ARCGIS_SC_SLUG', 'south-carolina'),
            'params' => json_decode(env('ARCGIS_SC_PARAMS', '{}'), true),
        ],

        /**
         * Tennessee
         */
        'tn' => [
            'url' => env('ARCGIS_TN_URL', 'https://services.arcgis.com/lvPBAGXeSupVUvx2/arcgis/rest/services/TNForestProductsCompanies/FeatureServer/0'),
            'description' => env('ARCGIS_TN_DESCRIPTION', 'Tennessee Forest Products Companies'),
            'slug' => env('ARCGIS_TN_SLUG', 'tennessee'),
            'params' => json_decode(env('ARCGIS_TN_PARAMS', '{}'), true),
        ],

        /**
         * Texas
         * uses our old data so we don't even need to mess with it!
         */
        // 'tx' => [
        //     'url' => env('ARCGIS_TX_URL', ''),
            // 'description' => 'Texas Mills',
        //     'slug' => 'texas',
        //     'params' => json_decode(env('ARCGIS_TX_PARAMS', '{"where": "Physical_S=\'TX\'"}'), true),
        // ],

        /**
         * Virginia?
         */
        // 'va' => [
        //     'url' => env('ARCGIS_VA_URL', ''),
            // 'description' => 'Virginia Mills',
        //     'slug' => 'virginia',
        // ],


        /*
        | Add further endpoints here following the same pattern, e.g.:
        |
        | 'my_layer' => [
        |     'url'         => env('ARCGIS_MY_LAYER_URL', 'https://services…/FeatureServer/0'),
        |     'description' => 'My Custom Layer',
        |     'disk'        => null,
        |     'geojson'     => 'arcgis/my_layer.geojson',
        |     'csv'         => 'arcgis/my_layer.csv',
        | ],
        */

    ],

];
