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
        //
    ];

    /**
     * Handle an incoming request (debug mode for troubleshooting)
     */
    public function handle($request, \Closure $next)
    {
        // Log CSRF debug info (remove in production)
        if (config('app.debug')) {
            Log::debug('CSRF Check', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'has_session' => $request->hasSession(),
                'session_token' => $request->session()->token(),
                'input_token' => $request->input('_token'),
                'header_token' => $request->header('X-CSRF-TOKEN'),
            ]);
        }

        return parent::handle($request, $next);
    }
}
