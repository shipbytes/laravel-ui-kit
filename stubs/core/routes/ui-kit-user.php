<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| UI Kit user-facing routes
|--------------------------------------------------------------------------
|
| Authenticated user routes that the kit's modules append to (e.g. /profile).
| Auto-loaded by UiKitServiceProvider when this file is present in routes/.
| Modules add lines between the markers below; reorder freely if you want.
*/

Route::middleware(['web', 'auth'])->group(function () {
    /* ui-kit:user-routes-start */
    /* ui-kit:user-routes-end */
});
