<?php

// ui-kit:managed — auto-loaded by UiKitServiceProvider. Delete this line to
// opt out of auto-loading and wire this file up yourself.

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| UI Kit public and user-facing routes
|--------------------------------------------------------------------------
|
| Pages the kit's modules add outside the admin panel. Auto-loaded by
| UiKitServiceProvider when this file is present in routes/. Modules add
| lines between the markers below; rename a path freely (keep the route
| name, the kit's links use it), or move a line out to routes/web.php.
*/

// Open to guests too (e.g. /contact, /changelog).
Route::middleware(['web'])->group(function () {
    /* ui-kit:public-routes-start */
    /* ui-kit:public-routes-end */
});

// Signed-in users only (e.g. /profile, /support).
Route::middleware(['web', 'auth'])->group(function () {
    /* ui-kit:user-routes-start */
    /* ui-kit:user-routes-end */
});
