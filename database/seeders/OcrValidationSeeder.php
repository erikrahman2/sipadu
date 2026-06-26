<?php

namespace Database\Seeders;

use App\Models\CaseModel;
use App\Models\Document;
use App\Models\OcrResult;
use App\Models\OcrValidation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OcrValidationSeeder extends Seeder
{
    public function run(): void
    {
        // Get seeder user for case submissions
        $submitter = User::where('email', 'asisten@pa-painan.go.id')->first();
        if (!$submitter) {
            $this->command->warn('PA Assistant user not found. Skipping OCR validation seed.');
            return;
        }

        // Create 6 cases with documents and OCR data
        $validationData = [
            // 1. Data Match - Perfect match
            [
                'case_number' => 'CASE-20260501-MATCH01',
                'document_type' => 'KTP',
                'status' => 'match',
                'input' => [
                    'nik' => '3174010101900001',
                    'nama' => 'Ahmad Wijaya',
                    'tempat_lahir' => 'Painan',
                    'tgl_lahir' => '1990-01-01',
                    'alamat' => 'Jl. Raya Painan No. 10',
                    'rt_rw' => '01/02',
                    'kelurahan' => 'Painan',
                    'kecamatan' => 'Painan',
                    'no_kk' => '3174010101900001',
                ],
                'ocr' => [
                    'nik' => '3174010101900001',
                    'nama' => 'Ahmad Wijaya',
                    'tempat_lahir' => 'Painan',
                    'tgl_lahir' => '1990-01-01',
                    'alamat' => 'Jl. Raya Painan No. 10',
                    'rt_rw' => '01/02',
                    'kelurahan' => 'Painan',
                    'kecamatan' => 'Painan',
                    'no_kk' => '3174010101900001',
                ],
                'match_score' => 100,
            ],
            // 2. Partial Match - 75% match
            [
                'case_number' => 'CASE-20260502-PARTIAL01',
                'document_type' => 'KTP',
                'status' => 'partial_match',
                'input' => [
                    'nik' => '3174010201900002',
                    'nama' => 'Budi Santoso',
                    'tempat_lahir' => 'Jakarta',
                    'tgl_lahir' => '1990-05-15',
                    'alamat' => 'Jl. Merdeka No. 25 Blok B',
                    'rt_rw' => '03/04',
                    'kelurahan' => 'Ciputat',
                    'kecamatan' => 'Ciputat Timur',
                    'no_kk' => '3174010201900002',
                ],
                'ocr' => [
                    'nik' => '3174010201900002',
                    'nama' => 'Budi Santoso',
                    'tempat_lahir' => 'Jakarta',
                    'tgl_lahir' => '1990-05-15',
                    'alamat' => 'Jl. Merdeka No. 25 Blok B',
                    'rt_rw' => '03/04',
                    'kelurahan' => 'Ciputat',
                    'kecamatan' => 'Ciputat Timur',
                    'no_kk' => '3174010201900003', // Mismatch
                ],
                'match_score' => 87,
            ],
            // 3. Partial Match - 75% match
            [
                'case_number' => 'CASE-20260503-PARTIAL02',
                'document_type' => 'KTP',
                'status' => 'partial_match',
                'input' => [
                    'nik' => '3174010301900003',
                    'nama' => 'Citra Dewi',
                    'tempat_lahir' => 'Bandung',
                    'tgl_lahir' => '1992-03-20',
                    'alamat' => 'Jl. Sudirman No. 15',
                    'rt_rw' => '05/06',
                    'kelurahan' => 'Sunter Jaya',
                    'kecamatan' => 'Sunter',
                    'no_kk' => '3174010301900003',
                ],
                'ocr' => [
                    'nik' => '3174010301900003',
                    'nama' => 'Citra Dewi',
                    'tempat_lahir' => 'Bandung',
                    'tgl_lahir' => '1992-03-20',
                    'alamat' => 'Jl. Sudirman No. 16', // Mismatch
                    'rt_rw' => '05/06',
                    'kelurahan' => 'Sunter Jaya',
                    'kecamatan' => 'Sunter',
                    'no_kk' => '3174010301900003',
                ],
                'match_score' => 85,
            ],
            // 4. Partial Match - 75% match
            [
                'case_number' => 'CASE-20260504-PARTIAL03',
                'document_type' => 'KTP',
                'status' => 'partial_match',
                'input' => [
                    'nik' => '3174010401900004',
                    'nama' => 'Doni Hermawan',
                    'tempat_lahir' => 'Yogyakarta',
                    'tgl_lahir' => '1988-07-12',
                    'alamat' => 'Jl. Ahmad Yani No. 50',
                    'rt_rw' => '07/08',
                    'kelurahan' => 'Tanjungsari',
                    'kecamatan' => 'Tanjungsari',
                    'no_kk' => '3174010401900004',
                ],
                'ocr' => [
                    'nik' => '3174010401900004',
                    'nama' => 'Doni Hermawan',
                    'tempat_lahir' => 'Yogyakarta',
                    'tgl_lahir' => '1988-07-12',
                    'alamat' => 'Jl. Ahmad Yani No. 50',
                    'rt_rw' => '07/08',
                    'kelurahan' => 'Tanjungsari',
                    'kecamatan' => 'Tanjungsari',
                    'no_kk' => '3174010401900005', // Mismatch
                ],
                'match_score' => 88,
            ],
            // 5. Partial Match - 75% match
            [
                'case_number' => 'CASE-20260505-PARTIAL04',
                'document_type' => 'KTP',
                'status' => 'partial_match',
                'input' => [
                    'nik' => '3174010501900005',
                    'nama' => 'Eka Prasetyo',
                    'tempat_lahir' => 'Surabaya',
                    'tgl_lahir' => '1991-11-08',
                    'alamat' => 'Jl. Diponegoro No. 75',
                    'rt_rw' => '09/10',
                    'kelurahan' => 'Mampang',
                    'kecamatan' => 'Mampang Prapatan',
                    'no_kk' => '3174010501900005',
                ],
                'ocr' => [
                    'nik' => '3174010501900005',
                    'nama' => 'Eka Prasetyo',
                    'tempat_lahir' => 'Surabaya',
                    'tgl_lahir' => '1991-11-08',
                    'alamat' => 'Jl. Diponegoro No. 75',
                    'rt_rw' => '09/10',
                    'kelurahan' => 'Mampang',
                    'kecamatan' => 'Mampang Prapatan',
                    'no_kk' => '3174010501900005',
                ],
                'match_score' => 86,
            ],
            // 6. Data Mismatch - Multiple mismatches
            [
                'case_number' => 'CASE-20260506-MISMATCH01',
                'document_type' => 'KTP',
                'status' => 'mismatch',
                'input' => [
                    'nik' => '3174010601900006',
                    'nama' => 'Farah Nabila',
                    'tempat_lahir' => 'Medan',
                    'tgl_lahir' => '1993-02-14',
                    'alamat' => 'Jl. Gatot Subroto No. 100',
                    'rt_rw' => '11/12',
                    'kelurahan' => 'Kebayoran Lama',
                    'kecamatan' => 'Kebayoran Lama',
                    'no_kk' => '3174010601900006',
                ],
                'ocr' => [
                    'nik' => '3174010601900007', // Mismatch
                    'nama' => 'Farah Nabila',
                    'tempat_lahir' => 'Palembang', // Mismatch
                    'tgl_lahir' => '1993-02-14',
                    'alamat' => 'Jl. Gatot Subroto No. 101', // Mismatch
                    'rt_rw' => '11/12',
                    'kelurahan' => 'Kebayoran Lama',
                    'kecamatan' => 'Kebayoran Lama',
                    'no_kk' => '3174010601900008', // Mismatch
                ],
                'match_score' => 50,
            ],
        ];

        // Create cases with OCR validations
        foreach ($validationData as $data) {
            $case = CaseModel::updateOrCreate(
                ['case_number' => $data['case_number']],
                [
                    'tracking_token' => 'TRK-' . strtoupper(Str::random(32)),
                    'submitter_id' => $submitter->id,
                    'institution_id' => $submitter->institution_id,
                    'petitioner_nik' => $data['input']['nik'],
                    'petitioner_name' => $data['input']['nama'],
                    'petitioner_alamat' => $data['input']['alamat'],
                    'status' => 'SUBMITTED',
                    'source_type' => 'INTERNAL',
                ]
            );

            // Create document
            $document = Document::updateOrCreate(
                ['case_id' => $case->id, 'document_type' => $data['document_type']],
                [
                    'uploaded_by' => $submitter->id,
                    'original_name' => 'document.pdf',
                    'stored_name' => 'doc-' . $case->id . '-' . time() . '.pdf',
                    'disk' => 'local',
                    'path' => 'uploads/documents/doc-' . $case->id . '.pdf',
                    'mime_type' => 'application/pdf',
                    'size_bytes' => 512000,
                    'status' => 'PROCESSED',
                ]
            );

            // Create OCR result
            $ocrResult = OcrResult::updateOrCreate(
                ['document_id' => $document->id],
                [
                    'case_id' => $case->id,
                    'nik' => $data['ocr']['nik'],
                    'nama' => $data['ocr']['nama'],
                    'tempat_lahir' => $data['ocr']['tempat_lahir'],
                    'tgl_lahir' => $data['ocr']['tgl_lahir'],
                    'alamat' => $data['ocr']['alamat'],
                    'rt_rw' => $data['ocr']['rt_rw'],
                    'kelurahan' => $data['ocr']['kelurahan'],
                    'kecamatan' => $data['ocr']['kecamatan'],
                    'no_kk' => $data['ocr']['no_kk'],
                    'overall_confidence' => $data['match_score'] / 100,
                    'ocr_status' => 'SUCCESS',
                ]
            );

            // Calculate fields matched based on status
            $fieldsMatched = $this->calculateFieldsMatched($data['input'], $data['ocr']);

            // Create OCR validation
            $statusMap = [
                'match' => 'MATCH',
                'partial_match' => 'PARTIAL_MATCH',
                'mismatch' => 'MISMATCH',
            ];

            // Generate comparison results
            $comparisonResults = $this->generateComparisonResults($data['input'], $data['ocr']);

            OcrValidation::updateOrCreate(
                ['ocr_result_id' => $ocrResult->id],
                [
                    'case_id' => $case->id,
                    'document_id' => $document->id,
                    'input_nik' => $data['input']['nik'],
                    'input_nama' => $data['input']['nama'],
                    'input_tempat_lahir' => $data['input']['tempat_lahir'],
                    'input_tgl_lahir' => $data['input']['tgl_lahir'],
                    'input_alamat' => $data['input']['alamat'],
                    'input_rt_rw' => $data['input']['rt_rw'],
                    'input_kelurahan' => $data['input']['kelurahan'],
                    'input_kecamatan' => $data['input']['kecamatan'],
                    'input_no_kk' => $data['input']['no_kk'],
                    'ocr_nik' => $data['ocr']['nik'],
                    'ocr_nama' => $data['ocr']['nama'],
                    'ocr_tempat_lahir' => $data['ocr']['tempat_lahir'],
                    'ocr_tgl_lahir' => $data['ocr']['tgl_lahir'],
                    'ocr_alamat' => $data['ocr']['alamat'],
                    'ocr_rt_rw' => $data['ocr']['rt_rw'],
                    'ocr_kelurahan' => $data['ocr']['kelurahan'],
                    'ocr_kecamatan' => $data['ocr']['kecamatan'],
                    'ocr_no_kk' => $data['ocr']['no_kk'],
                    'comparison_results' => json_encode($comparisonResults),
                    'overall_match_score' => $data['match_score'],
                    'fields_matched' => $fieldsMatched,
                    'fields_total' => 9,
                    'validation_status' => $statusMap[$data['status']] ?? 'MISMATCH',
                    'is_reviewed' => true,
                    'reviewed_by' => $submitter->id,
                    'reviewed_at' => now(),
                    'review_notes' => 'Seeded validation data',
                ]
            );

            $this->command->info("Created validation: {$data['case_number']} - {$data['status']}");
        }

        $this->command->info('✅ Seeded 6 OCR validations: 1 match, 4 partial matches, 1 mismatch');
    }

    /**
     * Calculate number of matched fields between input and OCR
     */
    private function calculateFieldsMatched(array $input, array $ocr): int
    {
        $fields = ['nik', 'nama', 'tempat_lahir', 'tgl_lahir', 'alamat', 'rt_rw', 'kelurahan', 'kecamatan', 'no_kk'];
        $matched = 0;

        foreach ($fields as $field) {
            if (($input[$field] ?? null) === ($ocr[$field] ?? null)) {
                $matched++;
            }
        }

        return $matched;
    }

    /**
     * Generate field-by-field comparison results
     */
    private function generateComparisonResults(array $input, array $ocr): array
    {
        $fields = ['nik', 'nama', 'tempat_lahir', 'tgl_lahir', 'alamat', 'rt_rw', 'kelurahan', 'kecamatan', 'no_kk'];
        $results = [];

        foreach ($fields as $field) {
            $inputVal = $input[$field] ?? null;
            $ocrVal = $ocr[$field] ?? null;
            $results[$field] = [
                'input' => $inputVal,
                'ocr' => $ocrVal,
                'match' => $inputVal === $ocrVal,
            ];
        }

        return $results;
    }
}
