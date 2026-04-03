<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PerformanceOptimizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Skip if not a regular web request
        if (!$response->headers->has('Content-Type')) {
            return $response;
        }

        // Set cache headers for static assets
        if ($this->isStaticAsset($request->path())) {
            $response->header('Cache-Control', 'public, max-age=31536000, immutable');
            $response->header('Pragma', 'cache');
        }
        // Set cache headers for API responses
        elseif ($request->is('api/*')) {
            $response->header('Cache-Control', 'public, max-age=300');
        }
        // Set cache headers for views (no cache for development)
        else {
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-Frame-Options', 'SAMEORIGIN');
        }

// Add ETag for cache validation (skip redirects and large responses)
        try {
            if (!$response->headers->has('ETag') && !$response->isRedirection()) {
                $content = $response->getContent();
                if (is_string($content) && strlen($content) < 1048576) { // skip if > 1 MB
                    $etag = md5($content);
                    $response->header('ETag', '"' . $etag . '"');

                    // If-None-Match handling
                    if ($request->header('If-None-Match') === '"' . $etag . '"') {
                        return response('', 304);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if ETag generation fails
        }

        return $response;
    }

    /**
     * Check if the request is for a static asset.
     */
    private function isStaticAsset(string $path): bool
    {
        return preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/', $path);
    }
}
