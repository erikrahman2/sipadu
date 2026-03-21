<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PublicSubmission;
use App\Models\CaseModel;
use App\Models\Document;
use App\Models\OcrResult;
use App\Models\OcrValidation;
use App\Models\User;
use App\Services\OCRValidationService;
use Illuminate\Support\Str;

echo "\n=== Membuat Sample Data Lengkap untuk OCR Validation ===\n\n";

// Cari atau buat user PA Management untuk processed_by
$paUser = User::where('email', 'ketua@pa-painan.go.id')->first();
if (!$paUser) {
    echo "❌ PA Management user tidak ditemukan\n";
    exit(1);
}

echo "✅ PA User: {$paUser->name} ({$paUser->email})\n";

// Gunakan PA user sebagai submitter (karena submitter_id required)
$submitter = $paUser;

echo "\n";

// SCENARIO 1: MATCH (98% similarity)
echo "========================\n";
echo "SCENARIO 1: MATCH (≥95%)\n";
echo "========================\n\n";

$ps1 = PublicSubmission::create([
    'tracking_token'   => 'TRK-' . strtoupper(Str::random(12)),
    'nik'              => '1301051905760001',  // NIK Aceh Selatan
    'petitioner_name'  => 'LINDO',
    'nama_lengkap'     => 'LINDO',
    'tempat_lahir'     => 'PADANG',
    'tanggal_lahir'    => '1976-05-19',
    'alamat'           => 'JL MERDEKA NO 10',
    'rt_rw'            => '001/002',
    'kelurahan'        => 'PAINAN',
    'kecamatan'        => 'IV JURAI',
    'no_kk'            => '1301050102030001',
    'phone_wa'         => '081234567890',
    'respondent_name'  => 'SITI AMINAH',
    'respondent_nik'   => '1301054512850002',
    'divorce_date'     => '2025-01-15',
    'verdict_number'   => '123/Pdt.G/2025/PA.PN',
    'status'           => 'APPROVED',
    'is_active'        => true,
    'processed_by'     => $paUser->id,
    'processed_at'     => now(),
]);

$case1 = CaseModel::create([
    'case_number'               => 'CASE-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
    'tracking_token'            => $ps1->tracking_token,
    'submitter_id'              => $submitter->id,
    'petitioner_nik'            => $ps1->nik,
    'petitioner_name'           => $ps1->petitioner_name,
    'petitioner_phone'          => $ps1->phone_wa,
    'institution_id'            => 1, // PA
    'spouse_nik'                => $ps1->respondent_nik,
    'spouse_name'               => $ps1->respondent_name,
    'divorce_date'              => $ps1->divorce_date,
    'verdict_number'            => $ps1->verdict_number,
    'status'                    => 'SUBMITTED',
    'assigned_pa_user_id'       => $paUser->id,
    'submitted_at'              => now(),
]);

$ps1->update(['case_id' => $case1->id]);

$doc1 = Document::create([
    'case_id'           => $case1->id,
    'document_type'     => 'KTP',
    'original_name'     => 'ktp_match.jpg',
    'stored_name'       => 'sample_ktp_match_' . time() . '.jpg',
    'path'              => 'documents/samples',
    'disk'              => 'public',
    'size_bytes'        => 245678,
    'mime_type'         => 'image/jpeg',
    'uploaded_by'       => $paUser->id,
    'status'            => 'PROCESSED',
]);

$ocr1 = OcrResult::create([
    'document_id'        => $doc1->id,
    'case_id'            => $case1->id,
    'public_submission_id' => $ps1->id,
    'status'             => 'SUCCESS',
    'nik'                => '1301051905760001',  // EXACT MATCH
    'nama'               => 'LINDO',             // EXACT MATCH
    'tempat_lahir'       => 'PADANG',            // EXACT MATCH
    'tanggal_lahir'      => '1976-05-19',        // EXACT MATCH
    'tgl_lahir'          => '19-05-1976',        // dd-mm-yyyy format
    'alamat'             => 'JL MERDEKA NO 10',  // EXACT MATCH
    'rt_rw'              => '001/002',           // EXACT MATCH
    'kelurahan'          => 'PAINAN',            // EXACT MATCH
    'kecamatan'          => 'IV JURAI',          // EXACT MATCH
    'no_kk'              => '1301050102030001',  // EXACT MATCH
    'confidence_scores'  => [
        'nik'          => 0.98,
        'nama'         => 0.96,
        'tempat_lahir' => 0.95,
        'tgl_lahir'    => 0.97,
        'alamat'       => 0.94,
        'rt_rw'        => 0.92,
        'kelurahan'    => 0.95,
        'kecamatan'    => 0.93,
        'no_kk'        => 0.96,
    ],
    'raw_response'       => json_encode(['scenario' => 'match']),
    'processed_at'       => now(),
]);

// Trigger validation
$validationService = app(OCRValidationService::class);
$validation1 = $validationService->compare($ocr1);

echo "✅ Public Submission: {$ps1->tracking_token}\n";
echo "✅ Case: {$case1->case_number}\n";
echo "✅ Document ID: {$doc1->id} (KTP)\n";
echo "✅ OCR Result ID: {$ocr1->id}\n";
echo "✅ Validation ID: {$validation1->id}\n";
echo "   Status: {$validation1->validation_status}\n";
echo "   Score: {$validation1->overall_match_score}%\n";
echo "   Matched Fields: {$validation1->fields_matched}/{$validation1->fields_total}\n\n";

// SCENARIO 2: PARTIAL_MATCH (80-94% similarity)
echo "================================\n";
echo "SCENARIO 2: PARTIAL_MATCH (80-94%)\n";
echo "================================\n\n";

$ps2 = PublicSubmission::create([
    'tracking_token'   => 'TRK-' . strtoupper(Str::random(12)),
    'nik'              => '1301051905760002',
    'petitioner_name'  => 'AHMAD DAHLAN',
    'nama_lengkap'     => 'AHMAD DAHLAN',
    'tempat_lahir'     => 'BANDUNG',
    'tanggal_lahir'    => '1980-03-25',
    'alamat'           => 'JL VETERAN NO 15 RT 003 RW 005',
    'rt_rw'            => '003/005',
    'kelurahan'        => 'KOTO BARU',
    'kecamatan'        => 'BATANG KAPAS',
    'no_kk'            => '1301050304050006',
    'phone_wa'         => '081234567891',
    'respondent_name'  => 'RINA SUSANTI',
    'respondent_nik'   => '1301055512880003',
    'divorce_date'     => '2025-02-20',
    'verdict_number'   => '456/Pdt.G/2025/PA.PN',
    'status'           => 'APPROVED',
    'is_active'        => true,
    'processed_by'     => $paUser->id,
    'processed_at'     => now(),
]);

$case2 = CaseModel::create([
    'case_number'               => 'CASE-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
    'tracking_token'            => $ps2->tracking_token,
    'submitter_id'              => $submitter->id,
    'petitioner_nik'            => $ps2->nik,
    'petitioner_name'           => $ps2->petitioner_name,
    'petitioner_phone'          => $ps2->phone_wa,
    'institution_id'            => 1,
    'spouse_nik'                => $ps2->respondent_nik,
    'spouse_name'               => $ps2->respondent_name,
    'divorce_date'              => $ps2->divorce_date,
    'verdict_number'            => $ps2->verdict_number,
    'status'                    => 'SUBMITTED',
    'assigned_pa_user_id'       => $paUser->id,
    'submitted_at'              => now(),
]);

$ps2->update(['case_id' => $case2->id]);

$doc2 = Document::create([
    'case_id'           => $case2->id,
    'document_type'     => 'KTP',
    'original_name'     => 'ktp_partial.jpg',
    'stored_name'       => 'sample_ktp_partial_' . time() . '.jpg',
    'path'              => 'documents/samples',
    'disk'              => 'public',
    'size_bytes'        => 258912,
    'mime_type'         => 'image/jpeg',
    'uploaded_by'       => $paUser->id,
    'status'            => 'PROCESSED',
]);

$ocr2 = OcrResult::create([
    'document_id'        => $doc2->id,
    'case_id'            => $case2->id,
    'public_submission_id' => $ps2->id,
    'status'             => 'SUCCESS',
    'nik'                => '1301051905760002',  // EXACT
    'nama'               => 'AHMAD DAHLANSYAH',  // Typo: extra "SYAH"
    'tempat_lahir'       => 'BANDUNG',           // EXACT
    'tanggal_lahir'      => '1980-03-25',        // EXACT
    'tgl_lahir'          => '25-03-1980',
    'alamat'             => 'JL VETERAN 15',     // Abbreviated (missing NO, RT, RW)
    'rt_rw'              => '003/005',           // EXACT
    'kelurahan'          => 'KOTA BARU',         // Typo: KOTA vs KOTO
    'kecamatan'          => 'BATANG KAPAS',      // EXACT
    'no_kk'              => '1301050304050006',  // EXACT
    'confidence_scores'  => [
        'nik'          => 0.97,
        'nama'         => 0.85,  // Lower due to typo
        'tempat_lahir' => 0.94,
        'tgl_lahir'    => 0.96,
        'alamat'       => 0.78,  // Lower due to abbreviation
        'rt_rw'        => 0.91,
        'kelurahan'    => 0.82,  // Lower due to typo
        'kecamatan'    => 0.93,
        'no_kk'        => 0.95,
    ],
    'raw_response'       => json_encode(['scenario' => 'partial_match']),
    'processed_at'       => now(),
]);

$validation2 = $validationService->compare($ocr2);

echo "✅ Public Submission: {$ps2->tracking_token}\n";
echo "✅ Case: {$case2->case_number}\n";
echo "✅ Document ID: {$doc2->id} (KTP)\n";
echo "✅ OCR Result ID: {$ocr2->id}\n";
echo "✅ Validation ID: {$validation2->id}\n";
echo "   Status: {$validation2->validation_status}\n";
echo "   Score: {$validation2->overall_match_score}%\n";
echo "   Matched Fields: {$validation2->fields_matched}/{$validation2->fields_total}\n\n";

// SCENARIO 3: MISMATCH (<70% similarity)
echo "===========================\n";
echo "SCENARIO 3: MISMATCH (<70%)\n";
echo "===========================\n\n";

$ps3 = PublicSubmission::create([
    'tracking_token'   => 'TRK-' . strtoupper(Str::random(12)),
    'nik'              => '1301052305850003',
    'petitioner_name'  => 'BUDI SANTOSO',
    'nama_lengkap'     => 'BUDI SANTOSO',
    'tempat_lahir'     => 'JAKARTA',
    'tanggal_lahir'    => '1985-05-23',
    'alamat'           => 'JL SUDIRMAN NO 88',
    'rt_rw'            => '002/003',
    'kelurahan'        => 'TAPAN',
    'kecamatan'        => 'TAPAN',
    'no_kk'            => '1301050708090010',
    'phone_wa'         => '081234567892',
    'respondent_name'  => 'DEWI LESTARI',
    'respondent_nik'   => '1301056612900004',
    'divorce_date'     => '2025-03-10',
    'verdict_number'   => '789/Pdt.G/2025/PA.PN',
    'status'           => 'APPROVED',
    'is_active'        => true,
    'processed_by'     => $paUser->id,
    'processed_at'     => now(),
]);

$case3 = CaseModel::create([
    'case_number'               => 'CASE-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
    'tracking_token'            => $ps3->tracking_token,
    'submitter_id'              => $submitter->id,
    'petitioner_nik'            => $ps3->nik,
    'petitioner_name'           => $ps3->petitioner_name,
    'petitioner_phone'          => $ps3->phone_wa,
    'institution_id'            => 1,
    'spouse_nik'                => $ps3->respondent_nik,
    'spouse_name'               => $ps3->respondent_name,
    'divorce_date'              => $ps3->divorce_date,
    'verdict_number'            => $ps3->verdict_number,
    'status'                    => 'SUBMITTED',
    'assigned_pa_user_id'       => $paUser->id,
    'submitted_at'              => now(),
]);

$ps3->update(['case_id' => $case3->id]);

$doc3 = Document::create([
    'case_id'           => $case3->id,
    'document_type'     => 'KTP',
    'original_name'     => 'ktp_mismatch.jpg',
    'stored_name'       => 'sample_ktp_mismatch_' . time() . '.jpg',
    'path'              => 'documents/samples',
    'disk'              => 'public',
    'size_bytes'        => 267543,
    'mime_type'         => 'image/jpeg',
    'uploaded_by'       => $paUser->id,
    'status'            => 'PROCESSED',
]);

$ocr3 = OcrResult::create([
    'document_id'        => $doc3->id,
    'case_id'            => $case3->id,
    'public_submission_id' => $ps3->id,
    'status'             => 'SUCCESS',
    'nik'                => '9999999999999999',       // COMPLETELY DIFFERENT
    'nama'               => 'ZAINAL ABIDIN',          // DIFFERENT PERSON
    'tempat_lahir'       => 'MEDAN',                  // DIFFERENT
    'tanggal_lahir'      => '1978-11-12',             // DIFFERENT
    'tgl_lahir'          => '12-11-1978',
    'alamat'             => 'JL GAJAH MADA NO 5',    // DIFFERENT
    'rt_rw'              => '010/015',                // DIFFERENT
    'kelurahan'          => 'AIRPURA',                // DIFFERENT
    'kecamatan'          => 'RANAH AMPEK HULU',       // DIFFERENT
    'no_kk'              => '9999999999999999',       // DIFFERENT
    'confidence_scores'  => [
        'nik'          => 0.92,  // High confidence but WRONG data!
        'nama'         => 0.91,
        'tempat_lahir' => 0.89,
        'tgl_lahir'    => 0.90,
        'alamat'       => 0.88,
        'rt_rw'        => 0.87,
        'kelurahan'    => 0.86,
        'kecamatan'    => 0.85,
        'no_kk'        => 0.90,
    ],
    'raw_response'       => json_encode(['scenario' => 'mismatch']),
    'processed_at'       => now(),
]);

$validation3 = $validationService->compare($ocr3);

echo "✅ Public Submission: {$ps3->tracking_token}\n";
echo "✅ Case: {$case3->case_number}\n";
echo "✅ Document ID: {$doc3->id} (KTP)\n";
echo "✅ OCR Result ID: {$ocr3->id}\n";
echo "✅ Validation ID: {$validation3->id}\n";
echo "   Status: {$validation3->validation_status}\n";
echo "   Score: {$validation3->overall_match_score}%\n";
echo "   Matched Fields: {$validation3->fields_matched}/{$validation3->fields_total}\n\n";

// Summary
echo "=============================\n";
echo "SUMMARY\n";
echo "=============================\n\n";

$allValidations = OcrValidation::whereIn('id', [$validation1->id, $validation2->id, $validation3->id])->get();

foreach ($allValidations as $val) {
    $icon = match($val->validation_status) {
        'MATCH' => '✅',
        'PARTIAL_MATCH' => '⚠️',
        'MANUAL_REVIEW' => '🔍',
        'MISMATCH' => '❌',
        default => '❓',
    };
    
    echo "{$icon} Validation #{$val->id}: {$val->validation_status} ({$val->overall_match_score}%)\n";
    echo "   Case: " . $val->case->case_number . "\n";
    echo "   Public Submission: " . $val->publicSubmission->tracking_token . "\n";
    echo "   Fields matched: {$val->fields_matched}/{$val->fields_total}\n\n";
}

echo "=============================\n";
echo "LOGIN & TEST\n";
echo "=============================\n\n";

echo "Login sebagai PA Management:\n";
echo "  Email: ketua@pa-painan.go.id\n";
echo "  Password: Pass@12345\n\n";

echo "URLs untuk test:\n";
echo "  - List Validasi: http://127.0.0.1:8000/dashboard/review/cases\n";
echo "  - Case #1 (MATCH): http://127.0.0.1:8000/dashboard/cases/{$case1->id}\n";
echo "  - Case #2 (PARTIAL): http://127.0.0.1:8000/dashboard/cases/{$case2->id}\n";
echo "  - Case #3 (MISMATCH): http://127.0.0.1:8000/dashboard/cases/{$case3->id}\n";
echo "  - Statistik: http://127.0.0.1:8000/dashboard/review/statistics\n\n";

echo "=== Selesai ===\n";
