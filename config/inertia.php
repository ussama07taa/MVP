<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    */

    'ssr' => [

        'enabled' => (bool) env('INERTIA_SSR_ENABLED', false),

        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),

        'ensure_bundle_exists' => (bool) env('INERTIA_SSR_ENSURE_BUNDLE_EXISTS', false),

    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */

    'ensure_pages_exist' => false,

    'page_paths' => [

        resource_path('js/Pages'),

    ],

    'page_extensions' => [

        'js',
        'jsx',
        'svelte',
        'ts',
        'tsx',
        'vue',

    ],

    /*
    |--------------------------------------------------------------------------
    | Initial Page Element
    |--------------------------------------------------------------------------
    |
    | @inertiajs/vue3 v3 reads the initial page payload from a
    | <script data-page="app" type="application/json"> tag. The legacy
    | <div id="app" data-page="..."> format is only read by Inertia JS v2
    | and below. Since this project uses @inertiajs/vue3 ^3.1.1, we MUST
    | enable script-tag rendering or the Vue tree will never hydrate
    | (initialPage is null -> "Cannot read properties of null (reading
    | 'component')").
    |
    */

    'use_script_element_for_initial_page' => (bool) env('INERTIA_USE_SCRIPT_ELEMENT_FOR_INITIAL_PAGE', true),

    'testing' => [

        'ensure_pages_exist' => true,

        'page_paths' => [

            resource_path('js/Pages'),

        ],

        'page_extensions' => [

            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',

        ],

    ],

    'history' => [

        'encrypt' => (bool) env('INERTIA_ENCRYPT_HISTORY', false),

    ],

];
