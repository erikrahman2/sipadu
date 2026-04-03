<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'auth/logout',       // POST logout - session being destroyed
        'auth/logout/*',     // Any logout sub-routes
    ];

    /**
     * Determine if the request has a valid CSRF token.
     * Override to skip CSRF check for logout routes entirely.
     */
    public function handle($request, \Closure $next)
    {
        // Skip CSRF validation for logout routes entirely
        if ($request->is('auth/logout') || $request->is('auth/logout/*')) {
            // Log logout attempt
            if (config('app.debug')) {
                Log::info('Logout request', [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'user' => $request->user()?->id,
                ]);
            }
            
            return $next($request);
        }

        // For all other routes, apply standard CSRF validation
        if (config('app.debug')) {
            Log::debug('CSRF Check', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'has_session' => $request->hasSession(),
                'session_token' => $request->session()->token(),
                'input_token' => $request->input('_token'),
            ]);
        }

        return parent::handle($request, $next);
    }
}

