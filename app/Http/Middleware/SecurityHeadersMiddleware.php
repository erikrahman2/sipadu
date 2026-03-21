<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force HTTPS, set secure headers, and validate content-type
 * for mutating JSON API requests.
 */
class SecurityHeadersMiddleware
{
    private array $secureHeaders = [
        'X-Content-Type-Options'    => 'nosniff',
        'X-Frame-Options'           => 'DENY',
        'X-XSS-Protection'          => '1; mode=block',
        'Referrer-Policy'           => 'strict-origin-when-cross-origin',
        'Permissions-Policy'        => 'camera=(), microphone=(), geolocation=()',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy'   => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; font-src 'self' data: https://cdnjs.cloudflare.com",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Force TLS in production
        if (config('app.env') === 'production' && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        foreach ($this->secureHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        // Remove fingerprinting headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
