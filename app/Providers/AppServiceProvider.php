<?php

namespace App\Providers;

use App\Services\AuditService;
use App\Services\GraphService;
use App\Services\OCRService;
use App\Services\ReBACService;
use App\Services\WorkflowService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singletons for heavyweight services
        $this->app->singleton(GraphService::class);
        $this->app->singleton(ReBACService::class);
        $this->app->singleton(OCRService::class);

        // Bind AuditService (per-request)
        $this->app->bind(AuditService::class, fn() => new AuditService());

        // WorkflowService depends on OCRService + AuditService
        $this->app->bind(WorkflowService::class, function ($app) {
            return new WorkflowService(
                $app->make(OCRService::class),
                $app->make(AuditService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // General API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('app.rate_limit_api', 60))
                        ->by($request->user()?->id ?: $request->ip());
        });

        // Auth endpoints
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(config('app.rate_limit_auth', 10))
                        ->by($request->ip())
                        ->response(fn() => response()->json([
                            'message' => 'Terlalu banyak percobaan. Coba lagi nanti.',
                        ], 429));
        });

        // OCR queue – global limit
        RateLimiter::for('ocr', function (Request $request) {
            return Limit::perMinute(config('app.rate_limit_ocr', 20))
                        ->by($request->user()?->id ?: $request->ip());
        });
    }
}
