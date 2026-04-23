<?php

namespace Shipbytes\UiKit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fallback admin gate used until the admin-middleware module is installed.
 *
 * Checks a boolean `is_admin` column on the authenticated User. When the
 * Spatie-backed module is installed, config/admin.php should be switched to
 * the richer gate (e.g. 'can:access-admin').
 */
class EnsureIsAdminFallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || empty($user->is_admin)) {
            abort(403, 'Admin access required.');
        }

        return $next($request);
    }
}
