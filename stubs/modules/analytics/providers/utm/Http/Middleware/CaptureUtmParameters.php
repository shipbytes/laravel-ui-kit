<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persists incoming ?utm_* parameters to session. On registration, copy them
 * onto the User (see the `save` callback in your register Volt page).
 */
class CaptureUtmParameters
{
    protected array $keys = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $captured = [];
        foreach ($this->keys as $key) {
            if ($request->filled($key)) {
                $captured[$key] = (string) $request->query($key);
            }
        }

        if (! empty($captured)) {
            $request->session()->put('utm', array_merge(
                (array) $request->session()->get('utm', []),
                $captured
            ));
        }

        return $next($request);
    }
}
