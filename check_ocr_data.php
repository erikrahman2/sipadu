<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CaseModel;
use App\Models\OcrResult;
use App\Models\OcrValidation;

echo "=== Checking Data ===\n\n";

// Case data
$case = CaseModel::find(5);
if ($case) {
    echo "Case #5:\n";
    echo "  NIK: {$case->petitioner_nik}\n";
    echo "  Nama: {$case->petitioner_name}\n";
    echo "  Tempat Lahir: {$case->petitioner_place_of_birth}\n";
    echo "  Tgl Lahir: {$case->petitioner_date_of_birth}\n";
    echo "  Alamat: {$case->petitioner_address}\n\n";
}

// OCR Results
echo "OCR Results:\n";
$ocrResults = OcrResult::where('case_id', 5)->get();
foreach ($ocrResults as $ocr) {
    echo "\n  OCR #{$ocr->id}:\n";
    echo "    NIK: {$ocr->nik}\n";
    echo "    Nama: {$ocr->nama}\n";
    echo "    Tempat Lahir: {$ocr->tempat_lahir}\n";
    echo "    Tgl Lahir: {$ocr->tgl_lahir}\n";
    echo "    Alamat: {$ocr->alamat}\n";
    echo "    Status: {$ocr->ocr_status}\n";
    echo "    Confidence: {$ocr->overall_confidence}\n";
}

// Validations
echo "\n\nOCR Validations:\n";
$validations = OcrValidation::where('case_id', 5)->get();
foreach ($validations as $val) {
    echo "\n  Validation #{$val->id}:\n";
    echo "    OCR Result ID: {$val->ocr_result_id}\n";
    echo "    Status: {$val->validation_status}\n";
    echo "    Score: {$val->overall_match_score}%\n";
    echo "    Input NIK: {$val->input_nik}\n";
    echo "    OCR NIK: {$val->ocr_nik}\n";
    echo "    Input Nama: {$val->input_nama}\n";
    echo "    OCR Nama: {$val->ocr_nama}\n";
}

echo "\n\nDone!\n";
