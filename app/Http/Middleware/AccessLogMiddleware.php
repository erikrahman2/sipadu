<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessLogMiddleware
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $ms = (int) round((microtime(true) - $startTime) * 1000);

        // Fire-and-forget (non-blocking)
        try {
            $this->audit->logAccess($request, $response->getStatusCode(), $ms);
        } catch (\Throwable) {
            // Never break the request pipeline
        }

        // Append timing header
        $response->headers->set('X-Response-Time', "{$ms}ms");

        return $response;
    }
}
