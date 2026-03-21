<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OcrResult;
use App\Services\OCRValidationService;

echo "=== Triggering OCR Validation for Existing Results ===\n\n";

$service = app(OCRValidationService::class);
$results = OcrResult::whereDoesntHave('validation')
    ->with('document.case.publicSubmission')
    ->get();

echo "Found {$results->count()} OCR results without validation\n\n";

foreach ($results as $result) {
    $document = $result->document;
    
    if (!$document) {
        echo "❌ OCR #{$result->id}: No document found\n";
        continue;
    }
    
    if (!$document->case_id) {
        echo "⚠️  OCR #{$result->id}: No case linked\n";
        continue;
    }
    
    try {
        $validation = $service->compare($result);
        $result->update(['has_validation' => true]);
        
        $emoji = match($validation->validation_status) {
            'MATCH' => '✅',
            'PARTIAL_MATCH' => '⚠️',
            'MANUAL_REVIEW' => '🔍',
            'MISMATCH' => '❌',
            default => '❓',
        };
        
        echo "{$emoji} OCR #{$result->id}: {$validation->validation_status} (Score: " . 
             number_format($validation->overall_match_score, 1) . "%)\n";
        echo "   Document Type: {$document->document_type}\n";
        echo "   Case ID: " . ($document->case_id ?? 'N/A') . "\n";
        
        if ($validation->validation_status === 'MISMATCH') {
            $mismatched = $validation->getMismatchedFields();
            if (!empty($mismatched)) {
                echo "   Mismatched fields: " . implode(', ', $mismatched) . "\n";
            }
        }
        
        echo "\n";
        
    } catch (\Exception $e) {
        echo "❌ Error validating OCR #{$result->id}: {$e->getMessage()}\n\n";
    }
}

echo "\n=== Summary ===\n";
echo "Total OCR validations created: " . \App\Models\OcrValidation::count() . "\n";
echo "\nValidation status breakdown:\n";
foreach (['MATCH', 'PARTIAL_MATCH', 'MANUAL_REVIEW', 'MISMATCH'] as $status) {
    $count = \App\Models\OcrValidation::where('validation_status', $status)->count();
    echo "  - {$status}: {$count}\n";
}

echo "\nDone!\n";
