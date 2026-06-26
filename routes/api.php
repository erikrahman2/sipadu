<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CaseController;
use App\Http\Controllers\API\DocumentController;
use App\Http\Controllers\API\OCRController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\SyncController;
use App\Http\Controllers\API\TrackingController;
use App\Http\Controllers\API\PublicSubmissionController as PublicApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  –  /api/v1/...
|--------------------------------------------------------------------------
*/

// ── Public ──────────────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('login',   [AuthController::class, 'login'])
             ->middleware('throttle:' . config('app.rate_limit_auth', 10) . ',1');
        Route::post('logout',  [AuthController::class, 'logout'])->middleware('auth:api');
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
        Route::get('me',       [AuthController::class, 'me'])->middleware('auth:api');
    });

    // Public tracking (no auth)
    Route::get('tracking/{token}', [TrackingController::class, 'track'])
         ->middleware('throttle:30,1');
    Route::get('tracking/{token}/download/{documentId}', [TrackingController::class, 'downloadDocument'])
         ->middleware('throttle:60,1');

    // Pengajuan publik (no auth) — rate limit per IP
    Route::prefix('public')->middleware('throttle:20,1')->group(function () {
        Route::post('submissions',           [PublicApiController::class, 'store']);
        Route::post('submissions/check-nik', [PublicApiController::class, 'checkNik']);
        Route::get('submissions/{token}',    [PublicApiController::class, 'track']);
    });

    // ── Protected ─────────────────────────────────────────────────────────────
    Route::middleware(['auth:api', 'security.headers', 'access.log'])->group(function () {

        // Cases
        Route::apiResource('cases', CaseController::class)
             ->only(['index', 'store', 'show']);
        Route::patch('cases/{id}/assign', [CaseController::class, 'assign']);

        // Documents
        Route::post('documents/upload',          [DocumentController::class, 'upload']);
        Route::get('documents/download/{id}',    [DocumentController::class, 'download']);
        Route::get('documents/{id}',             [DocumentController::class, 'show']);

        // OCR
        Route::post('ocr/process',               [OCRController::class, 'process']);
        Route::get('ocr/result/{id}',            [OCRController::class, 'result']);
        Route::get('ocr/job/{id}',               [OCRController::class, 'jobStatus']);

        // Review workflow
        Route::post('review/pa',                 [ReviewController::class, 'paReview']);
        Route::post('review/disdukcapil',        [ReviewController::class, 'disdukcapilReview']);
        Route::post('review/submit/{caseId}',    [ReviewController::class, 'submitCase']);

        // Graph sync (admin)
        Route::prefix('sync')->middleware('role:super_admin')->group(function () {
            Route::post('graph',  [SyncController::class, 'triggerSync']);
            Route::get('status',  [SyncController::class, 'status']);
        });
    });
});
