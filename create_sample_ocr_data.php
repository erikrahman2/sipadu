<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CaseModel;
use App\Models\Document;
use App\Models\OcrResult;
use App\Models\OcrValidation;
use App\Services\OCRValidationService;

echo "=== Creating Sample Data with Better OCR Results ===\n\n";

// Get case #5
$case = CaseModel::find(5);
if (!$case) {
    echo "❌ Case #5 not found\n";
    exit(1);
}

echo "Case: {$case->case_number}\n";
echo "NIK: {$case->petitioner_nik}\n";
echo "Nama: {$case->petitioner_name}\n\n";

// Delete old OCR results and validations
echo "Cleaning old data...\n";
OcrValidation::where('case_id', 5)->delete();
OcrResult::where('case_id', 5)->delete();

// Create good OCR result (MATCH)
echo "\n1. Creating MATCH scenario...\n";
$doc1 = Document::where('case_id', 5)->first();
if ($doc1) {
    $ocrMatch = OcrResult::create([
        'document_id' => $doc1->id,
        'case_id' => 5,
        'nik' => '1301051905760001',     // Same as case
        'nama' => 'LINDO',               // Uppercase version
        'tempat_lahir' => 'Padang',
        'tgl_lahir' => '1976-05-19',
        'alamat' => 'Jl. Contoh No. 123',
        'rt_rw' => '001/002',
        'kelurahan' => 'Kelurahan Test',
        'kecamatan' => 'Kecamatan Test',
        'no_kk' => '1301051234567890',
        'ocr_status' => 'SUCCESS',
        'overall_confidence' => 0.98,
        'confidence_scores' => [
            'nik' => 0.99,
            'nama' => 0.97,
            'alamat' => 0.96,
        ],
        'engine_version' => 'tesseract-5.5.0',
        'processing_time_ms' => 1250,
    ]);
    
    // Trigger validation
    $service = app(OCRValidationService::class);
    $validation = $service->compare($ocrMatch);
    $ocrMatch->update(['has_validation' => true]);
    
    echo "   ✅ OCR #{$ocrMatch->id}: {$validation->validation_status} (Score: " . 
         number_format($validation->overall_match_score, 1) . "%)\n";
}

// Create partial match (small differences)
echo "\n2. Creating PARTIAL_MATCH scenario...\n";
$doc2 = Document::where('case_id', 5)->skip(1)->first();
if ($doc2) {
    $ocrPartial = OcrResult::create([
        'document_id' => $doc2->id,
        'case_id' => 5,
        'nik' => '1301051905760001',     // Same
        'nama' => 'LLINDO',              // Type slight typo
        'tempat_lahir' => 'Pdg',         // Abbreviation
        'tgl_lahir' => '1976-05-19',
        'alamat' => 'Jl Contok No 123',  // Typo in "Contoh"
        'rt_rw' => '001/002',
        'kelurahan' => 'Kel Test',       // Abbreviated
        'kecamatan' => 'Kec Test',
        'no_kk' => '1301051234567890',
        'ocr_status' => 'SUCCESS',
        'overall_confidence' => 0.85,
        'confidence_scores' => [
            'nik' => 0.99,
            'nama' => 0.82,
            'alamat' => 0.78,
        ],
        'engine_version' => 'tesseract-5.5.0',
        'processing_time_ms' => 1150,
    ]);
    
    $validation = $service->compare($ocrPartial);
    $ocrPartial->update(['has_validation' => true]);
    
    echo "   ⚠️ OCR #{$ocrPartial->id}: {$validation->validation_status} (Score: " . 
         number_format($validation->overall_match_score, 1) . "%)\n";
}

// Create mismatch (NIK different)
echo "\n3. Creating MISMATCH scenario...\n";
$doc3 = Document::where('case_id', 5)->skip(2)->first();
if ($doc3) {
    $ocrMismatch = OcrResult::create([
        'document_id' => $doc3->id,
        'case_id' => 5,
        'nik' => '1301059999999999',     // DIFFERENT NIK!
        'nama' => 'BUDI SANTOSO',        // Different person
        'tempat_lahir' => 'Jakarta',
        'tgl_lahir' => '1980-01-01',
        'alamat' => 'Jl. Lain No. 456',
        'rt_rw' => '003/004',
        'kelurahan' => 'Kelurahan Lain',
        'kecamatan' => 'Kecamatan Lain',
        'no_kk' => '9999999999999999',
        'ocr_status' => 'SUCCESS',
        'overall_confidence' => 0.92,
        'confidence_scores' => [
            'nik' => 0.95,
            'nama' => 0.90,
            'alamat' => 0.91,
        ],
        'engine_version' => 'tesseract-5.5.0',
        'processing_time_ms' => 1300,
    ]);
    
    $validation = $service->compare($ocrMismatch);
    $ocrMismatch->update(['has_validation' => true]);
    
    echo "   ❌ OCR #{$ocrMismatch->id}: {$validation->validation_status} (Score: " . 
         number_format($validation->overall_match_score, 1) . "%)\n";
    if ($validation->validation_status === 'MISMATCH') {
        $mismatched = $validation->getMismatchedFields();
        if (!empty($mismatched)) {
            echo "      Mismatched: " . implode(', ', $mismatched) . "\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Total OCR validations: " . OcrValidation::where('case_id', 5)->count() . "\n\n";

foreach (['MATCH', 'PARTIAL_MATCH', 'MANUAL_REVIEW', 'MISMATCH'] as $status) {
    $count = OcrValidation::where('case_id', 5)->where('validation_status', $status)->count();
    if ($count > 0) {
        echo "  {$status}: {$count}\n";
    }
}

echo "\n✅ Sample data created successfully!\n";
echo "\n📝 View data at: http://127.0.0.1:8000/dashboard/review/cases/5\n";
echo "📊 View statistics: http://127.0.0.1:8000/dashboard/review/statistics\n\n";
