<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Document;
use App\Models\Case as CaseModel;

// Get recent cases with document counts
$recentCases = CaseModel::withCount('documents')
    ->has('documents')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();

if ($recentCases->isEmpty()) {
    echo "❌ Tidak ada dokumen ditemukan dalam database.\n\n";
    exit;
}

echo "=== DOKUMEN TERAKHIR DI-UPLOAD ===\n\n";

foreach ($recentCases as $case) {
    echo "📋 CASE #{$case->id} - {$case->case_number}\n";
    echo "   Tipe Kasus: {$case->type}\n";
    echo "   Total Dokumen: {$case->documents_count}\n";
    echo "   ---\n";
    
    // Get documents for this case, ordered by most recent
    $documents = $case->documents()
        ->where('deleted_at', null)
        ->orderBy('created_at', 'desc')
        ->get();
    
    foreach ($documents as $doc) {
        echo "   📄 {$doc->original_name}\n";
        echo "      Type: {$doc->document_type}\n";
        echo "      Stored Name: {$doc->stored_name}\n";
        echo "      Status: {$doc->status}\n";
        echo "      Uploaded: {$doc->created_at->format('Y-m-d H:i:s')}\n";
        echo "      Size: " . round($doc->size_bytes / 1024, 2) . " KB\n";
        echo "\n";
    }
    
    echo "\n";
}

echo "✅ Query selesai.\n";
