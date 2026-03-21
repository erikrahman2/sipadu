<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\CaseModel;

echo "=== Debugging PA Management Access ===\n\n";

// Get PA Management user
$user = User::where('email', 'ketua@pa-painan.go.id')->first();
if (!$user) {
    echo "❌ PA Management user not found!\n";
    exit(1);
}

echo "👤 User: {$user->name}\n";
echo "   ID: {$user->id}\n";
echo "   Institution ID: {$user->institution_id}\n";
echo "   Institution: " . ($user->institution->name ?? 'None') . "\n";
echo "   Roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";

// Check cases
$cases = CaseModel::all();
echo "📂 Cases:\n";
foreach ($cases as $case) {
    echo "   Case ID: {$case->id}\n";
    echo "   Case Number: {$case->case_number}\n";
    echo "   Institution ID: {$case->institution_id}\n";
    echo "   Submitter ID: {$case->submitter_id}\n";
    echo "   Created By: {$case->created_by}\n";
    echo "   Status: {$case->status}\n";
    
    // Check MySQL conditions
    echo "   MySQL checks:\n";
    echo "      - Submitter match: " . ($case->submitter_id === $user->id ? 'YES' : 'NO') . "\n";
    echo "      - Institution match: " . ($case->institution_id === $user->institution_id ? 'YES' : 'NO') . "\n";
    echo "\n";
}

// Now test graph path
$graph = app(\App\Services\GraphService::class);

echo "🔍 Testing Neo4j paths for PA Management...\n\n";
$testCase = $cases->first();

echo "Test Case: {$testCase->case_number} (ID: {$testCase->id})\n\n";

// Test SUBMITTED path
echo "1. Testing path: User -[SUBMITTED]-> Case\n";
$hasSubmitted = $graph->pathExists('User', $user->id, 'Case', $testCase->id, ['SUBMITTED']);
echo "   Result: " . ($hasSubmitted ? 'EXISTS ✅' : 'NOT FOUND ❌') . "\n\n";

// Test WORKS_AT -> MANAGES path
echo "2. Testing path: User -[WORKS_AT]-> Institution -[MANAGES]-> Case\n";
$hasWorksAtManages = $graph->pathExists('User', $user->id, 'Case', $testCase->id, ['WORKS_AT', 'MANAGES']);
echo "   Result: " . ($hasWorksAtManages ? 'EXISTS ✅' : 'NOT FOUND ❌') . "\n\n";

// Test ReBAC directly
$rebac = app(\App\Services\ReBACService::class);

// Clear cache first
\Illuminate\Support\Facades\Cache::flush();

echo "3. Testing ReBACService->permit()\n";
$canView = $rebac->permit($user, 'view', 'Case', $testCase->id);
echo "   View: " . ($canView ? 'ALLOWED ✅' : 'DENIED ❌') . "\n";

$canEdit = $rebac->permit($user, 'edit', 'Case', $testCase->id);
echo "   Edit: " . ($canEdit ? 'ALLOWED ✅' : 'DENIED ❌') . "\n";

$canApprove = $rebac->permit($user, 'approve', 'Case', $testCase->id);
echo "   Approve: " . ($canApprove ? 'ALLOWED ✅' : 'DENIED ❌') . "\n\n";

// Check if user has roles
echo "4. Role check for approve:\n";
echo "   Has pa_management role: " . ($user->hasRole('pa_management') ? 'YES ✅' : 'NO ❌') . "\n";
echo "   Has pa_staff role: " . ($user->hasRole('pa_staff') ? 'YES ✅' : 'NO ❌') . "\n";
echo "   Has either role: " . ($user->hasAnyRole(['pa_management', 'pa_staff']) ? 'YES ✅' : 'NO ❌') . "\n";

echo "\n✨ Debug complete!\n";
