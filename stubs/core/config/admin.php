<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin route configuration
    |--------------------------------------------------------------------------
    */

    'route_prefix' => 'admin',
    'route_name_prefix' => 'admin.',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Applied to every admin route. When the admin-middleware module is
    | installed, replace 'can:access-admin' with your Spatie permission.
    | Until then the fallback checks an is_admin column on the User model.
    */

    'middleware' => ['auth', 'verified', \Shipbytes\UiKit\Http\Middleware\EnsureIsAdminFallback::class],

    /*
    |--------------------------------------------------------------------------
    | Sidebar navigation
    |--------------------------------------------------------------------------
    |
    | Each entry is either:
    |   - ['section' => 'Label']                          — divider heading
    |   - ['label' => ..., 'route' => ..., 'icon' => ..., 'badge' => 'open_tickets']
    |
    | Modules append their own entries via the install command.
    | `icon` is a key into stubs/core/views/layouts/partials/nav-icons.blade.php.
    */

    'nav' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'grid'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
        /* ui-kit:nav-start */
        /* ui-kit:nav-end */
    ],
];
