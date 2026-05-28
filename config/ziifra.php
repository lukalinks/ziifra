<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | Keys are BCP 47 language codes. Organization locale must match a key
    | here; unknown values fall back to the application fallback locale.
    |
    */

    'locales' => [
        'en' => 'English',
        'sq' => 'Shqip',
        'de' => 'Deutsch',
        'sr' => 'Srpski',
        'fr' => 'Français',
        'hr' => 'Hrvatski',
    ],

    'locale_definitions' => [
        'en' => [
            'label' => 'English',
            'native' => 'English',
        ],
        'sq' => [
            'label' => 'Albanian',
            'native' => 'Shqip',
        ],
        'de' => [
            'label' => 'German',
            'native' => 'Deutsch',
        ],
        'sr' => [
            'label' => 'Serbian',
            'native' => 'Srpski',
        ],
        'fr' => [
            'label' => 'French',
            'native' => 'Français',
        ],
        'hr' => [
            'label' => 'Croatian',
            'native' => 'Hrvatski',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Link preview (Open Graph / Twitter)
    |--------------------------------------------------------------------------
    |
    | default_image: absolute URL or path under public/ (e.g. /og/custom.png).
    | Organization logos use the public /o/{slug}/brand/logo route when set.
    |
    */

    'social' => [
        'default_description' => env('SOCIAL_DEFAULT_DESCRIPTION'),
        'default_image' => env('SOCIAL_DEFAULT_IMAGE'),
        'image_width' => 1024,
        'image_height' => 682,
    ],

];
