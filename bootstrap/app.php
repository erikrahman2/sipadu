<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:     __DIR__.'/../routes/web.php',
        api:     __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health:  '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Redirect unauthenticated web users to login
        $middleware->redirectGuestsTo(fn () => route('auth.login'));

        // Global middleware - CSRF sudah included by default di web group
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\PerformanceOptimizationMiddleware::class,
        ]);

        // Aliases
        $middleware->alias([
            'rebac'           => \App\Http\Middleware\ReBACMiddleware::class,
            'security.headers'=> \App\Http\Middleware\SecurityHeadersMiddleware::class,
            'access.log'      => \App\Http\Middleware\AccessLogMiddleware::class,
            'role'            => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'      => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'auto.logout'     => \App\Http\Middleware\AutoLogoutOnPublicPage::class,
        ]);

        // API rate limiting
        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Web 403 → redirect to appropriate dashboard based on role (role mismatch)
        $exceptions->renderable(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if (! $request->expectsJson()) {
                // Super admin yang coba akses halaman petugas → ke panel admin
                if (auth()->check() && auth()->user()->hasRole('super_admin')) {
                    return redirect()->route('dashboard.admin.users')
                        ->with('error', 'Halaman tersebut hanya untuk petugas lapangan. Anda dialihkan ke panel admin.');
                }
                // Role lain → ke dashboard mereka
                return redirect()->route('dashboard.index')
                    ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            }
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'HTTP Error',
                    'status'  => $e->getStatusCode(),
                ], $e->getStatusCode());
            }
        });

        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi sudah berubah atau kedaluwarsa. Muat ulang halaman lalu kirim ulang form.',
                ], 419);
            }

            return redirect()->back()
                ->withInput($request->except('_token'))
                ->with('error', 'Sesi form kedaluwarsa. Halaman telah disegarkan, silakan kirim ulang.');
        });

        // Handle fatal errors (timeout, memory) - prevent Ignition retry loop
        $exceptions->renderable(function (\Symfony\Component\ErrorHandler\Error\FatalError $e, $request) {
            \Illuminate\Support\Facades\Log::error('Fatal Error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Processing error occurred. Please try again.',
                ], 500);
            }

            return response()->view('errors.500', [], 500);
        });
    })
    ->create();
