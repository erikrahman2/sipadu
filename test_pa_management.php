<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\CaseModel;
use App\Models\OcrValidation;
use Illuminate\Support\Facades\Route;

echo "=== Testing PA Management Pages ===\n\n";

// 1. Check user
$paManagement = User::whereHas('roles', function($q) {
    $q->where('name', 'pa_management');
})->first();

if (!$paManagement) {
    echo "❌ No PA Management user found\n";
    exit(1);
}

echo "✅ Found PA Management user: {$paManagement->name} ({$paManagement->email})\n\n";

// 2. Check data
echo "Data Summary:\n";
echo "  - Cases: " . CaseModel::count() . "\n";
echo "  - Cases with validations: " . CaseModel::has('ocrValidations')->count() . "\n";
echo "  - OCR Validations: " . OcrValidation::count() . "\n";
echo "  - MATCH: " . OcrValidation::where('validation_status', 'MATCH')->count() . "\n";
echo "  - PARTIAL_MATCH: " . OcrValidation::where('validation_status', 'PARTIAL_MATCH')->count() . "\n";
echo "  - MANUAL_REVIEW: " . OcrValidation::where('validation_status', 'MANUAL_REVIEW')->count() . "\n";
echo "  - MISMATCH: " . OcrValidation::where('validation_status', 'MISMATCH')->count() . "\n\n";

// 3. Check routes
echo "Routes Check:\n";
$routes = [
    'dashboard.review.cases' => '/dashboard/review/cases',
    'dashboard.review.show' => '/dashboard/review/cases/{id}',
    'dashboard.review.validate' => '/dashboard/review/cases/{id}/validate',
    'dashboard.review.statistics' => '/dashboard/review/statistics',
];

foreach ($routes as $name => $uri) {
    $route = Route::getRoutes()->getByName($name);
    if ($route) {
        echo "  ✅ {$name}: {$route->uri()}\n";
    } else {
        echo "  ❌ {$name}: NOT FOUND\n";
    }
}

// 4. Test controller methods exist
echo "\nController Methods Check:\n";
$controller = new \App\Http\Controllers\Web\ReviewController();
$methods = ['index', 'show', 'validateOcr', 'statistics'];
foreach ($methods as $method) {
    if (method_exists($controller, $method)) {
        echo "  ✅ ReviewController::{$method}()\n";
    } else {
        echo "  ❌ ReviewController::{$method}() NOT FOUND\n";
    }
}

// 5. Test view files exist
echo "\nView Files Check:\n";
$views = [
    'dashboard.review.index' => 'resources/views/dashboard/review/index.blade.php',
    'dashboard.review.show' => 'resources/views/dashboard/review/show.blade.php',
    'dashboard.review.statistics' => 'resources/views/dashboard/review/statistics.blade.php',
];

foreach ($views as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "  ✅ {$name}\n";
    } else {
        echo "  ❌ {$name} NOT FOUND\n";
    }
}

// 6. Sample case with validation
echo "\nSample Case with Validation:\n";
$caseWithValidation = CaseModel::has('ocrValidations')->with('ocrValidations')->first();
if ($caseWithValidation) {
    echo "  Case: {$caseWithValidation->case_number}\n";
    echo "  Validations: {$caseWithValidation->ocrValidations->count()}\n";
    echo "  URL: http://127.0.0.1:8000/dashboard/review/cases/{$caseWithValidation->id}\n";
    foreach ($caseWithValidation->ocrValidations as $val) {
        echo "    - Validation #{$val->id}: {$val->validation_status} ({$val->overall_match_score}%)\n";
    }
} else {
    echo "  ❌ No case with validation found\n";
}

echo "\n✅ All tests completed!\n";
echo "\n==== NEXT STEPS ====\n";
echo "1. Login sebagai PA Management: {$paManagement->email}\n";
echo "2. Akses menu 'Validasi OCR' di sidebar\n";
echo "3. Atau akses langsung: http://127.0.0.1:8000/dashboard/review/cases\n";
echo "4. Klik 'Review' untuk melihat detail validasi\n\n";
