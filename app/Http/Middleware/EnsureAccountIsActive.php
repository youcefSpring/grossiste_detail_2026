<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A disabled or deleted account must stop working at once.
 *
 * Login checks is_active, but a cashier who is disabled — or deleted — while
 * logged in keeps a valid session until it expires. This ends it on their
 * next request.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->is_active || $user->trashed())) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(401, __('auth.inactive'));
            }

            return redirect()->route('login')->with('error', __('auth.inactive'));
        }

        return $next($request);
    }
}
