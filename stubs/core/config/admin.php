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
    | `icon` is a Heroicons v2 outline name (https://heroicons.com), rendered
    | through the design system's ds::icon, e.g. 'squares-2x2', 'users',
    | 'document-text'. A name that does not resolve shows a question-mark icon
    | rather than failing the page; `php artisan ui-kit:doctor` names it.
    */

    'nav' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'squares-2x2'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
        /* ui-kit:nav-start */
        /* ui-kit:nav-end */
    ],
];
