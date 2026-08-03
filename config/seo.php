<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Meta Description
    |--------------------------------------------------------------------------
    |
    | Used whenever a page doesn't supply its own description. Placeholder
    | copy below -- replace with real marketing copy.
    */

    'default_description' => env(
        'SEO_DEFAULT_DESCRIPTION',
        'Find sawmills, pulp mills, and other forest product processors near you.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Default Open Graph Type
    |--------------------------------------------------------------------------
    */

    'default_og_type' => 'website',

    /*
    |--------------------------------------------------------------------------
    | Twitter Handle
    |--------------------------------------------------------------------------
    |
    | Used for the twitter:site meta tag, e.g. "@example". Omitted if null.
    */

    'twitter_handle' => env('SEO_TWITTER_HANDLE'),

];
