<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\PublicSubmissionController;
use App\Http\Controllers\Web\PublicSubmissionStaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => view('welcome-new'))->middleware('auto.logout')->name('home');
Route::redirect('/login', '/auth/login')->name('login');

// ── Pengajuan Publik (tanpa autentikasi) ─────────────────────────────────────
// auto.logout: jika ada staf yang sudah login, otomatis logout sebelum lanjut
Route::prefix('pengajuan')
     ->name('public.submit.')
     ->middleware(['auto.logout', 'throttle:30,1'])
     ->group(function () {
         Route::get('/',          [PublicSubmissionController::class, 'create'])->name('create');
         Route::post('/',         [PublicSubmissionController::class, 'store'])->name('store');
         Route::post('/cek-nik',  [PublicSubmissionController::class, 'checkNik'])->name('check_nik');
         Route::get('/sukses/{token}', [PublicSubmissionController::class, 'success'])->name('success');
     });

// ── Public Tracking ──────────────────────────────────────────────────────────
Route::middleware('auto.logout')->group(function () {
    Route::get('/tracking', fn() => view('tracking.public'))->name('tracking.public');
    Route::get('/tracking/{token}', fn(string $token) => view('tracking.public', ['token' => $token]))
         ->name('tracking.token');
    Route::get('/lacak/{token}', fn(string $token) => view('tracking.public', ['token' => $token]))
         ->name('public.tracking.token');
});

// ── Tentang & Berita ─────────────────────────────────────────────────────────
Route::middleware('auto.logout')->group(function () {
    Route::get('/tentang', fn() => view('tentang'))->name('tentang');
    Route::get('/berita', fn() => view('berita'))->name('berita');
});

// ── Auth ────────────────────────────────────────────────────────────────────
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::post('logout',[AuthController::class, 'logout'])->name('logout')->middleware('auth');
});

// ── Dashboard (protected) ────────────────────────────────────────────────────
Route::middleware(['auth', 'security.headers', 'access.log'])->prefix('dashboard')->name('dashboard.')->group(function () {

    // Dashboard utama – semua role yang terautentikasi
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    // Kasus – hanya petugas lapangan (bukan super_admin)
    // PENTING: cases/create harus didefinisikan sebelum cases/{id}
    // agar string "create" tidak ditangkap sebagai parameter {id} dinamis
    Route::middleware('role:pa_assistant|pa_management|pa_staff|disdukcapil_staff')
         ->get('/cases', [DashboardController::class, 'cases'])->name('cases');

    Route::middleware('role:pa_assistant')->group(function () {
        Route::get('/cases/create', [DashboardController::class, 'createCase'])->name('cases.create');
        Route::post('/cases', [DashboardController::class, 'storeCase'])->name('cases.store');
    });

    Route::middleware('role:pa_assistant|pa_management|pa_staff|disdukcapil_staff')
         ->get('/cases/{id}', [DashboardController::class, 'showCase'])->name('cases.show');

    // Upload dokumen – PA Assistant & PA Staff
    Route::middleware('role:pa_assistant|pa_staff')
         ->get('/upload', [DashboardController::class, 'upload'])->name('upload');

    // Tracking – semua petugas lapangan (bukan super_admin)
    Route::middleware('role:pa_assistant|pa_management|pa_staff|disdukcapil_staff')
         ->get('/tracking', [DashboardController::class, 'tracking'])->name('tracking');

    // OCR viewer – semua petugas lapangan (bukan super_admin)
    Route::middleware('role:pa_assistant|pa_management|pa_staff|disdukcapil_staff')
         ->get('/ocr/{id}', [DashboardController::class, 'ocrResult'])->name('ocr.result');

    // Admin – super_admin only
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users',  [DashboardController::class, 'users'])->name('users');
        Route::get('/sync',   [DashboardController::class, 'syncStatus'])->name('sync');
        Route::get('/audit',  [DashboardController::class, 'audit'])->name('audit');
        Route::get('/logs',   [DashboardController::class, 'logs'])->name('logs');
    });

    // Kotak masuk pengajuan publik – PA Assistant, PA Management, Disdukcapil, PA Staff
    // (super_admin dikecualikan: tugas super_admin hanya monitoring pengguna & audit)
    Route::middleware('role:pa_assistant|pa_management|disdukcapil_staff|pa_staff')
         ->prefix('public-inbox')
         ->name('public-inbox.')
         ->group(function () {
             Route::get('/',                [PublicSubmissionStaffController::class, 'index'])->name('index');
             Route::get('/{id}',            [PublicSubmissionStaffController::class, 'show'])->name('show');
             Route::post('/{id}/review',    [PublicSubmissionStaffController::class, 'startReview'])->name('review');
             Route::post('/{id}/approve',   [PublicSubmissionStaffController::class, 'approve'])->name('approve');
             Route::post('/{id}/reject',    [PublicSubmissionStaffController::class, 'reject'])->name('reject');
             Route::post('/{id}/resend-wa', [PublicSubmissionStaffController::class, 'resendWa'])->name('resend_wa');
         });
    
    // ── OCR Validation Review (PA Management & Super Admin) ─────────────────
    Route::middleware('role:pa_management|super_admin')
         ->prefix('review')
         ->name('review.')
         ->group(function () {
             Route::get('/cases',            [\App\Http\Controllers\Web\ReviewController::class, 'index'])->name('cases');
             Route::get('/cases/{id}',       [\App\Http\Controllers\Web\ReviewController::class, 'show'])->name('show');
                Route::post('/cases/{id}/correct', [\App\Http\Controllers\Web\ReviewController::class, 'correctOcr'])->name('correct');
                Route::post('/cases/{id}/refresh-ocr', [\App\Http\Controllers\Web\ReviewController::class, 'refreshOcr'])->name('refresh_ocr');
             Route::post('/cases/{id}/validate', [\App\Http\Controllers\Web\ReviewController::class, 'validateOcr'])->name('validate');
             Route::get('/statistics',       [\App\Http\Controllers\Web\ReviewController::class, 'statistics'])->name('statistics');
         });
});
