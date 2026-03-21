<?php

namespace App\Http\Middleware;

use App\Services\ReBACService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ReBAC Policy Enforcement Middleware.
 *
 * Usage in route:
 *   ->middleware('rebac:view,Case,case')
 *
 * Parameters:
 *   $action        – action string (view, edit, approve, ...)
 *   $resourceType  – graph label (Case, Document, ...)
 *   $routeParam    – name of the route parameter that holds the resource ID
 */
class ReBACMiddleware
{
    public function __construct(private readonly ReBACService $rebac) {}

    public function handle(Request $request, Closure $next, string $action, string $resourceType, string $routeParam = 'id'): Response
    {
        $user       = auth()->user();
        $resourceId = (int) $request->route($routeParam);

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $this->rebac->enforce($user, $action, $resourceType, $resourceId);

        return $next($request);
    }
}
