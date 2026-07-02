<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Track last activity time in session for auto-logout on inactivity.
 */
class TrackLastActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            Session::put('last_activity', now()->timestamp);
        }

        return $next($request);
    }
}
