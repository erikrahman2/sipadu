<?php

use App\Models\CmsBlogPost;
use App\Models\CmsAboutSection;
use App\Models\CmsHomeSection;
use App\Http\Controllers\Web\ContentController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\PublicSubmissionController;
use App\Http\Controllers\Web\PublicSubmissionStaffController;
use App\Http\Controllers\Web\StaffController;
use App\Http\Controllers\Web\PagesController;
use App\Http\Controllers\Admin\CMSKelolaKontenController;
use App\Http\Controllers\Admin\CmsBlogPostController;
use App\Http\Controllers\Admin\CmsHomeSectionController;
use App\Http\Controllers\Admin\CmsAboutSectionController;
use App\Http\Controllers\Admin\CmsLayanController;
use App\Http\Controllers\Web\PublicPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Load debug routes (remove in production)
if (config('app.debug')) {
    require __DIR__ . '/debug.php';
}

// Load homepage with CMS data
Route::get('/', function () {
    $blogPosts = CmsBlogPost::published()
        ->orderByDesc('published_at')
        ->take(10)
        ->get();
    $sections = CmsHomeSection::where('is_active', 1)->orderBy('display_order')->get()->keyBy('content_type');

    // Map content_type from admin CMS to view variables
    // Urutan di sini mengikuti urutan section di halaman publik (welcome-new.blade.php)
    //   home_seo           = SEO meta (title, content)
    //   hero               = Headline Utama (title, subtitle, cta_label, cta_url, secondary_cta_url)
    //   proses_metodologi  = Cara Kerja / Process Timeline (title, subtitle)
    //   fitur_unggulan     = Layanan / Fitur Unggulan (title, subtitle)
    //   statistik          = Angka & Statistik (title, subtitle)
    //   blog_header        = Judul Halaman Berita (title, subtitle)
    //   cta_footer         = Ajakan Bertindak CTA (title, subtitle, cta_label, cta_url)
    $sSeo        = $sections->get('home_seo')             ?? (object)['title'=>'','subtitle'=>'','content'=>''];
    $sHero       = $sections->get('hero')                 ?? (object)['title'=>'','subtitle'=>'','cta_label'=>'','cta_url'=>null,'secondary_cta_url'=>null];
    $sProses     = $sections->get('proses_metodologi')    ?? (object)['title'=>'','subtitle'=>'','content'=>''];
    $sFitur      = $sections->get('fitur_unggulan')       ?? (object)['title'=>'','subtitle'=>'','content'=>''];
    $sStatistik  = $sections->get('statistik')            ?? (object)['title'=>'','subtitle'=>'','content'=>''];
    $sBlog       = $sections->get('blog_header')          ?? (object)['title'=>'','subtitle'=>'','content'=>''];
    $sCta        = $sections->get('cta_footer')           ?? (object)['title'=>'Siap untuk memulai?','subtitle'=>'','cta_label'=>'','cta_url'=>null];

    return view('welcome-new', compact(
        'blogPosts',
        'sHero', 'sProses', 'sFitur', 'sStatistik', 'sCta', 'sBlog', 'sSeo'
    ));
})->middleware('auto.logout')->name('home');

// Halaman Layanan / Services
Route::get('/layanan', [\App\Http\Controllers\Web\ContentController::class, 'layananPage'])->name('services');

Route::redirect('/login', '/auth/login')->name('login');

// Pengajuan Publik (tanpa autentikasi)
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

// Public Tracking
Route::middleware('auto.logout')->group(function () {
    Route::get('/tracking', fn() => view('tracking.public'))->name('tracking.public');
    Route::get('/tracking/{token}', fn(string $token) => view('tracking.public', ['token' => $token]))
         ->name('tracking.token');
    Route::get('/lacak/{token}', fn(string $token) => view('tracking.public', ['token' => $token]))
         ->name('public.tracking.token');
});

// Tentang & Berita
Route::middleware('auto.logout')->group(function () {
    Route::get('/tentang', [ContentController::class, 'aboutPage'])->name('tentang');
    Route::get('/berita', [ContentController::class, 'blogPage'])->name('berita');
    Route::get('/berita/{slug}', [ContentController::class, 'blogPost'])->name('berita.detail');
});

// Auth
Route::prefix('auth')->name('auth.')->middleware('web')->group(function () {
    Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::post('logout',[AuthController::class, 'logout'])->name('logout')->middleware('auth');
    Route::get('logout',  [AuthController::class, 'logout'])->name('logout.get')->middleware('auth');
});

// Keepalive endpoint — bumps session last_activity without changing page
Route::post('/keepalive', function () {
    \Illuminate\Support\Facades\Session::put('last_activity', now()->timestamp);
    return response()->json(['ok' => true]);
})->middleware(['auth', 'track.activity'])->name('keepalive');

// Dashboard (protected)
Route::middleware(['auth', 'security.headers', 'access.log', 'track.activity'])->prefix('dashboard')->name('dashboard.')->group(function () {

    // Dashboard utama â€“ semua role yang terautentikasi
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    // Kasus â€“ hanya petugas lapangan (bukan super_admin)
    // PENTING: cases/create harus didefinisikan sebelum cases/{id}
    // agar string "create" tidak ditangkap sebagai parameter {id} dinamis
    Route::middleware('role:pa_assistant|pa_management|pa_staff|disdukcapil_staff')
         ->get('/cases', [DashboardController::class, 'cases'])->name('cases');

    Route::middleware('role:pa_assistant')->group(function () {
        Route::get('/cases/create', [DashboardController::class, 'createCase'])->name('cases.create');
        Route::post('/cases', [DashboardController::class, 'storeCase'])->name('cases.store');
        Route::post('/cases/save-draft', [DashboardController::class, 'saveDraftCase'])->name('cases.save-draft');
        Route::get('/cases/{id}/edit-draft', [DashboardController::class, 'editDraftCase'])->name('cases.edit-draft');
        Route::patch('/cases/{id}/update-draft', [DashboardController::class, 'updateDraftCase'])->name('cases.update-draft');
        Route::post('/cases/{id}/submit-draft', [DashboardController::class, 'submitDraftCase'])->name('cases.submit-draft');
        Route::post('/cases/from-public/{publicSubmission}', [DashboardController::class, 'createFromPublicSubmission'])->name('cases.from-public');
    });

    Route::middleware('role:pa_assistant|pa_management|pa_staff|disdukcapil_staff')
         ->get('/cases/{id}', [DashboardController::class, 'showCase'])->name('cases.show');

    // Upload dokumen â€“ PA Assistant & PA Staff
    Route::middleware('role:pa_assistant|pa_staff')
         ->get('/upload', [DashboardController::class, 'upload'])->name('upload');

    // Tracking â€“ semua petugas lapangan (bukan super_admin)
    Route::middleware('role:pa_assistant|pa_management|pa_staff|disdukcapil_staff')
         ->get('/tracking', [DashboardController::class, 'tracking'])->name('tracking');

    // OCR viewer â€“ semua petugas lapangan (bukan super_admin)
    Route::middleware('role:pa_assistant|pa_management|pa_staff|disdukcapil_staff')
         ->get('/ocr/{id}', [DashboardController::class, 'ocrResult'])->name('ocr.result');

    // Admin â€“ super_admin only
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users',  [DashboardController::class, 'users'])->name('users');
        Route::get('/sync',   [DashboardController::class, 'syncStatus'])->name('sync');
        Route::get('/audit',  [DashboardController::class, 'audit'])->name('audit');
        Route::get('/logs',   [DashboardController::class, 'logs'])->name('logs');
    });

    // CMS â€“ CRUD halaman publik (Blog, Home, About)
    Route::middleware('role:pa_staff|pa_management|super_admin')->prefix('admin/cms')->name('admin.cms.')->group(function () {
        // Blog / Berita
        Route::get('/blog',          [CmsBlogPostController::class, 'index'])->name('blog.index');
        Route::get('/blog/create',   [CmsBlogPostController::class, 'create'])->name('blog.create');
        Route::post('/blog',         [CmsBlogPostController::class, 'store'])->name('blog.store');
        Route::get('/blog/{post}',   [CmsBlogPostController::class, 'edit'])->name('blog.edit');
        Route::patch('/blog/{post}', [CmsBlogPostController::class, 'update'])->name('blog.update');
        Route::delete('/blog/{post}', [CmsBlogPostController::class, 'destroy'])->name('blog.destroy');

        // Home Sections
        Route::get('/home',          [CmsHomeSectionController::class, 'index'])->name('home.index');
        Route::get('/home/create',   [CmsHomeSectionController::class, 'create'])->name('home.create');
        Route::post('/home',         [CmsHomeSectionController::class, 'store'])->name('home.store');
        Route::get('/home/{home}/edit', [CmsHomeSectionController::class, 'edit'])->name('home.edit');
        Route::patch('/home/{home}',  [CmsHomeSectionController::class, 'update'])->name('home.update');
        Route::delete('/home/{home}', [CmsHomeSectionController::class, 'destroy'])->name('home.destroy');

        // About Sections
        Route::get('/about',         [CmsAboutSectionController::class, 'index'])->name('about.index');
        Route::get('/about/create',  [CmsAboutSectionController::class, 'create'])->name('about.create');
        Route::post('/about',        [CmsAboutSectionController::class, 'store'])->name('about.store');
        Route::get('/about/{about}/edit', [CmsAboutSectionController::class, 'edit'])->name('about.edit');
        Route::patch('/about/{about}',  [CmsAboutSectionController::class, 'update'])->name('about.update');
        Route::delete('/about/{about}', [CmsAboutSectionController::class, 'destroy'])->name('about.destroy');

        // Layanan
        Route::get('/layanan',             [CmsLayanController::class, 'index'])->name('layan.index');
        Route::get('/layanan/create',      [CmsLayanController::class, 'create'])->name('layan.create');
        Route::post('/layanan',            [CmsLayanController::class, 'store'])->name('layan.store');
        Route::get('/layanan/{layan}/edit',[CmsLayanController::class, 'edit'])->name('layan.edit');
        Route::patch('/layanan/{layan}',   [CmsLayanController::class, 'update'])->name('layan.update');
        Route::delete('/layanan/{layan}',  [CmsLayanController::class, 'destroy'])->name('layan.destroy');

        // Kelola Konten – unified tabs index (beranda / tentang / berita)
        Route::get('/kelola-konten', [\App\Http\Controllers\Admin\CMSKelolaKontenController::class, 'index'])
            ->name('kelola-konten.index');

        // Unified Kelola Konten (legacy)
        Route::get('/kelola-konten-legacy', [CMSKelolaKontenController::class, 'index'])->name('kelola-konten-legacy.index');

        // Kelola Konten – Home
        Route::post('/kelola-konten/home',        [CMSKelolaKontenController::class, 'homeStore'])->name('kelola-konten.home.store');
        Route::get('/kelola-konten/home/create',  [CMSKelolaKontenController::class, 'homeCreate'])->name('kelola-konten.home.create');
        Route::get('/kelola-konten/home/{home}/edit', [CMSKelolaKontenController::class, 'homeEdit'])->name('kelola-konten.home.edit');
        Route::patch('/kelola-konten/home/{home}',  [CMSKelolaKontenController::class, 'homeUpdate'])->name('kelola-konten.home.update');
        Route::delete('/kelola-konten/home/{home}', [CMSKelolaKontenController::class, 'homeDestroy'])->name('kelola-konten.home.destroy');

        // Kelola Konten – About
        Route::post('/kelola-konten/about',        [CMSKelolaKontenController::class, 'aboutStore'])->name('kelola-konten.about.store');
        Route::get('/kelola-konten/about/create',  [CMSKelolaKontenController::class, 'aboutCreate'])->name('kelola-konten.about.create');
        Route::get('/kelola-konten/about/{about}/edit', [CMSKelolaKontenController::class, 'aboutEdit'])->name('kelola-konten.about.edit');
        Route::patch('/kelola-konten/about/{about}',  [CMSKelolaKontenController::class, 'aboutUpdate'])->name('kelola-konten.about.update');
        Route::delete('/kelola-konten/about/{about}', [CMSKelolaKontenController::class, 'aboutDestroy'])->name('kelola-konten.about.destroy');

        // Kelola Konten – Blog
        Route::post('/kelola-konten/blog',         [CMSKelolaKontenController::class, 'blogStore'])->name('kelola-konten.blog.store');
        Route::get('/kelola-konten/blog/create',   [CMSKelolaKontenController::class, 'blogCreate'])->name('kelola-konten.blog.create');
        Route::get('/kelola-konten/blog/{post}/edit', [CMSKelolaKontenController::class, 'blogEdit'])->name('kelola-konten.blog.edit');
        Route::patch('/kelola-konten/blog/{post}',  [CMSKelolaKontenController::class, 'blogUpdate'])->name('kelola-konten.blog.update');
        Route::delete('/kelola-konten/blog/{post}', [CMSKelolaKontenController::class, 'blogDestroy'])->name('kelola-konten.blog.destroy');
    });

    // Aktivitas & Arsip â€“ pa_staff, pa_management
    Route::middleware('role:pa_staff|pa_management')
         ->prefix('aktivitas')
         ->name('aktivitas.')
         ->group(function () {
             Route::get('/',            [DashboardController::class, 'aktivitas'])->name('index');
             Route::get('/terbaru',     [DashboardController::class, 'aktivitasTerbaru'])->name('terbaru');
             Route::get('/arsip',       [DashboardController::class, 'arsip'])->name('arsip');
             Route::get('/arsip/{id}',  [DashboardController::class, 'showArsip'])->name('arsip.show');
             Route::post('/{id}/restore', [DashboardController::class, 'restoreArsip'])->name('restore');
         });

    // Kotak masuk pengajuan publik â€“ PA Assistant, PA Management, Disdukcapil, PA Staff
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

    // PA Staff: Aktivitas Terbaru & Arsip
    Route::middleware('role:pa_staff')
         ->prefix('staff')
         ->name('staff.')
         ->group(function () {
             Route::get('/aktivitas',           [DashboardController::class, 'staffAktivitas'])->name('aktivitas');
             Route::get('/arsip',               [DashboardController::class, 'staffArsip'])->name('arsip');
             Route::get('/arsip/{id}',          [DashboardController::class, 'staffArsipShow'])->name('arsip.show');
             Route::get('/arsip/{id}/download', [DashboardController::class, 'staffArsipDownload'])->name('arsip.download');
         });

    // OCR Validation Review (PA Management & Super Admin)
    Route::middleware('role:pa_management|super_admin')
         ->prefix('review')
         ->name('review.')
         ->group(function () {
             Route::get('/cases',            [\App\Http\Controllers\Web\ReviewController::class, 'index'])->name('cases');
             Route::get('/all-data',         [\App\Http\Controllers\Web\ReviewController::class, 'allData'])->name('all_data');
             Route::get('/cases/{id}',       [\App\Http\Controllers\Web\ReviewController::class, 'show'])->name('show');
             Route::post('/cases/{id}/correct', [\App\Http\Controllers\Web\ReviewController::class, 'correctOcr'])->name('correct');
             Route::post('/cases/{id}/refresh-ocr', [\App\Http\Controllers\Web\ReviewController::class, 'refreshOcr'])->name('refresh_ocr');
             Route::post('/cases/{id}/validate', [\App\Http\Controllers\Web\ReviewController::class, 'validateOcr'])->name('validate');
             Route::post('/cases/{id}/send-to-disdukcapil', [\App\Http\Controllers\Web\ReviewController::class, 'sendToDisdukcapil'])->name('send_to_disdukcapil');
             Route::get('/statistics',       [\App\Http\Controllers\Web\ReviewController::class, 'statistics'])->name('statistics');
         });

    // Disdukcapil Validation Process (Disdukcapil Staff & Super Admin)
    Route::middleware('role:disdukcapil_staff|super_admin')
         ->prefix('disdukcapil')
         ->name('disdukcapil.')
         ->group(function () {
             Route::get('/cases',                    [\App\Http\Controllers\Web\DisdukcapilController::class, 'index'])->name('index');
             Route::get('/cases/{id}',               [\App\Http\Controllers\Web\DisdukcapilController::class, 'show'])->name('show');
             Route::get('/cases/{id}/process',      [\App\Http\Controllers\Web\DisdukcapilController::class, 'showProcess'])->name('process.show');
             Route::post('/cases/{id}/process',     [\App\Http\Controllers\Web\DisdukcapilController::class, 'submitProcess'])->name('process.submit');
         });
});

