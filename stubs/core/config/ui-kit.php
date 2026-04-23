<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    |
    | Parameterizes the logo, name, and public home route referenced by the
    | guest/admin layouts. Override these in any host application without
    | touching the published blade files.
    */

    'brand' => [
        'name' => env('UI_KIT_BRAND_NAME', config('app.name', 'App')),
        'logo' => env('UI_KIT_BRAND_LOGO', '/images/logo.png'),
        'home_route' => env('UI_KIT_HOME_ROUTE', 'home'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth page copy
    |--------------------------------------------------------------------------
    */

    'copy' => [
        'login' => [
            'heading' => 'Welcome back',
            'subheading' => 'Sign in to continue.',
        ],
        'register' => [
            'heading' => 'Create your account',
            'subheading' => 'Start your free account in seconds.',
        ],
        'forgot_password' => [
            'heading' => 'Forgot your password?',
            'subheading' => "We'll email you a reset link.",
        ],
        'reset_password' => [
            'heading' => 'Reset your password',
            'subheading' => 'Pick something strong.',
        ],
        'verify_email' => [
            'heading' => 'Verify your email',
            'subheading' => "We sent a link to your inbox. Can't find it? Resend below.",
        ],
        'confirm_password' => [
            'heading' => 'Confirm your password',
            'subheading' => 'Please confirm your password to continue.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature toggles
    |--------------------------------------------------------------------------
    |
    | These let you disable behaviors without uninstalling their views.
    */

    'features' => [
        'disposable_email_block' => true,
        'socialite' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Installed modules (managed by ui-kit:install-module)
    |--------------------------------------------------------------------------
    |
    | Do not edit by hand unless you know what you're doing.
    */

    'installed_modules' => [],
];
