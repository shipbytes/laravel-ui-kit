<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return $next($request);
        }

        abort(403, 'Admin access required.');
    }
}
