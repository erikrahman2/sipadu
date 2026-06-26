<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\OcrResult;
use App\Models\OcrValidation;
use App\Models\PublicSubmission;
use Illuminate\Support\Facades\Log;

class OCRValidationService
{
    /**
     * Ambil data input manual dari Case atau PublicSubmission
     */
    public function getInputData($caseId = null, $publicSubmissionId = null, ?string $documentType = null): array
    {
        if ($caseId) {
            $case = CaseModel::with('publicSubmission')->find($caseId);
            if (!$case) {
                return [];
            }

            $isWifeKtp = $documentType === 'KTP_ISTRI';
            
            // Prioritas: ambil dari public_submission jika ada (lebih lengkap),
            // fallback ke case data (terbatas)
            if ($case->publicSubmission) {
                $ps = $case->publicSubmission;

                if ($isWifeKtp) {
                    return [
                        'nik'           => $ps->respondent_nik ?? $case->spouse_nik,
                        'nama'          => $ps->respondent_name ?? $case->spouse_name,
                        'alamat'        => $case->spouse_alamat,
                        'rt_rw'         => $case->spouse_rt_rw,
                        'kelurahan'     => $case->spouse_kelurahan,
                        'kecamatan'     => $case->spouse_kecamatan,
                    ];
                }

                return [
                    'nik'           => $ps->nik ?? $case->petitioner_nik,
                    'nama'          => $ps->nama_lengkap ?? $case->petitioner_name,
                    'alamat'        => $ps->alamat ?? $case->petitioner_alamat,
                    'rt_rw'         => $ps->rt_rw ?? $case->petitioner_rt_rw,
                    'kelurahan'     => $ps->kelurahan ?? $case->petitioner_kelurahan,
                    'kecamatan'     => $ps->kecamatan ?? $case->petitioner_kecamatan,
                ];
            }
            
            // Data case (terbatas)
            if ($isWifeKtp) {
                return [
                    'nik'           => $case->spouse_nik,
                    'nama'          => $case->spouse_name,
                    'alamat'        => $case->spouse_alamat,
                    'rt_rw'         => $case->spouse_rt_rw,
                    'kelurahan'     => $case->spouse_kelurahan,
                    'kecamatan'     => $case->spouse_kecamatan,
                ];
            }

            return [
                'nik'           => $case->petitioner_nik,
                'nama'          => $case->petitioner_name,
                'alamat'        => $case->petitioner_alamat,
                'rt_rw'         => $case->petitioner_rt_rw,
                'kelurahan'     => $case->petitioner_kelurahan,
                'kecamatan'     => $case->petitioner_kecamatan,
            ];
        }
        
        if ($publicSubmissionId) {
            $submission = PublicSubmission::find($publicSubmissionId);
            if (!$submission) {
                return [];
            }
            
            if ($documentType === 'KTP_ISTRI') {
                return [
                    'nik'           => $submission->respondent_nik,
                    'nama'          => $submission->respondent_name,
                    'alamat'        => null,
                    'rt_rw'         => null,
                    'kelurahan'     => null,
                    'kecamatan'     => null,
                ];
            }

            return [
                'nik'           => $submission->nik,
                'nama'          => $submission->nama_lengkap ?? $submission->petitioner_name,
                'alamat'        => $submission->alamat ?? null,
                'rt_rw'         => $submission->rt_rw ?? null,
                'kelurahan'     => $submission->kelurahan ?? null,
                'kecamatan'     => $submission->kecamatan ?? null,
            ];
        }
        
        return [];
    }
    
    /**
     * Bandingkan data input vs OCR dan simpan hasil validasi
     */
    public function compare(OcrResult $ocrResult): OcrValidation
    {
        $ocrResult->loadMissing('document');
        $documentType = optional($ocrResult->document)->document_type;

        // Ambil data input original
        $inputData = $this->getInputData(
            $ocrResult->case_id,
            $ocrResult->public_submission_id,
            $documentType
        );
        
        // Fields yang akan dibandingkan
        $fieldsToCompare = [
            'nik', 'nama', 'alamat', 'rt_rw', 'kelurahan', 'kecamatan'
        ];
        
        $comparisonResults = [];
        $matchedCount = 0;
        $totalFields = 0;
        $weightedScore = 0;
        $totalWeight = 0;
        
        // Define field weights (critical fields weighted more)
        $fieldWeights = [
            'nik' => 0.35,      // 35% - Most critical
            'nama' => 0.30,     // 30% - Important
            'alamat' => 0.15,   // 15%
            'kelurahan' => 0.10, // 10%
            'rt_rw' => 0.05,    // 5%
            'kecamatan' => 0.05, // 5%
        ];
        
        foreach ($fieldsToCompare as $field) {
            $inputValue = $inputData[$field] ?? null;
            $ocrValue = $this->getOcrFieldValue($ocrResult, $field);
            
            // Skip jika kedua field kosong
            if (empty($inputValue) && empty($ocrValue)) {
                continue;
            }
            
            $totalFields++;
            $weight = $fieldWeights[$field] ?? 0.05;
            $totalWeight += $weight;
            
            // Normalisasi untuk perbandingan
            $inputNormalized = $this->normalize($inputValue);
            $ocrNormalized = $this->normalize($ocrValue);
            
            // Hitung similarity dengan approach yang lebih lenient untuk low-quality OCR
            $similarity = $this->calculateSimilarityLenient($inputNormalized, $ocrNormalized, $field);
            
            // Threshold lebih lenient untuk handle OCR noise
            $threshold = match($field) {
                'nik' => 0.90,      // NIK: allow some digit OCR errors (0/O, 1/I)
                'nama' => 0.70,     // Nama: allow partial matches
                'alamat' => 0.50,   // Alamat: OCR sering error, allow very lenient matching
                'kelurahan' => 0.50, // Kelurahan: token-based matching
                'rt_rw' => 0.85,    // RT/RW: numeric, usually accurate
                'kecamatan' => 0.60,  // Kecamatan: allow some variation
            };
            
            $isMatch = $similarity >= $threshold;
            
            if ($isMatch) {
                $matchedCount++;
                $weightedScore += $weight;
            }
            
            $comparisonResults[$field] = [
                'input'      => $inputValue,
                'ocr'        => $ocrValue,
                'similarity' => round($similarity, 4),
                'match'      => $isMatch,
                'confidence' => $ocrResult->confidence_scores[$field] ?? 0,
                'threshold'  => $threshold,
                'weight'     => $weight,
            ];
        }
        
        // Hitung overall match score menggunakan rata-rata SEMUA field scores
        // Ini lebih fair daripada hanya count field yang match, karena perlihatkan quality komparatif
        $allScores = [];
        foreach ($comparisonResults as $field => $result) {
            $similarity = (float)($result['similarity'] ?? 0);  // 0-1 scale
            // Convert ke 0-100 percentage
            $allScores[] = $similarity * 100;
        }
        
        $overallScore = count($allScores) > 0
            ? round(array_sum($allScores) / count($allScores), 2)
            : 0;
        
        // Tentukan validation status
        $validationStatus = $this->determineValidationStatus($overallScore, $comparisonResults);
        
        // Simpan hasil validasi
        $validation = OcrValidation::updateOrCreate(
            [
                'ocr_result_id' => $ocrResult->id,
            ],
            [
                'case_id'               => $ocrResult->case_id,
                'public_submission_id'  => $ocrResult->public_submission_id,
                'document_id'           => $ocrResult->document_id,
                
                // Snapshot input
                'input_nik'             => $inputData['nik'] ?? null,
                'input_nama'            => $inputData['nama'] ?? null,
                'input_tempat_lahir'    => null,
                'input_tgl_lahir'       => null,
                'input_alamat'          => $inputData['alamat'] ?? null,
                'input_rt_rw'           => $inputData['rt_rw'] ?? null,
                'input_kelurahan'       => $inputData['kelurahan'] ?? null,
                'input_kecamatan'       => $inputData['kecamatan'] ?? null,
                'input_no_kk'           => null,
                
                // Snapshot OCR
                'ocr_nik'               => $ocrResult->nik,
                'ocr_nama'              => $ocrResult->nama,
                'ocr_tempat_lahir'      => $ocrResult->tempat_lahir,
                'ocr_tgl_lahir'         => $ocrResult->tgl_lahir,
                'ocr_alamat'            => $ocrResult->alamat,
                'ocr_rt_rw'             => $ocrResult->rt_rw,
                'ocr_kelurahan'         => $ocrResult->kelurahan,
                'ocr_kecamatan'         => $ocrResult->kecamatan,
                'ocr_no_kk'             => $ocrResult->no_kk,
                
                // Results
                'comparison_results'    => $comparisonResults,
                'overall_match_score'   => $overallScore,
                'fields_matched'        => $matchedCount,
                'fields_total'          => $totalFields,
                'validation_status'     => $validationStatus,
            ]
        );
        
        Log::channel('ocr')->info('OCR validation created', [
            'validation_id' => $validation->id,
            'ocr_result_id' => $ocrResult->id,
            'status'        => $validationStatus,
            'score'         => $overallScore,
        ]);
        
        return $validation;
    }
    
    /**
     * Normalisasi string untuk perbandingan yang lebih robust
     */
    private function normalize(?string $value): string
    {
        if (empty($value)) {
            return '';
        }
        
        // Convert to string if date
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d');
        }
        
        // Uppercase, hapus spasi berlebih
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        // Remove common OCR artifacts
        $normalized = str_replace(['|', '1', '0O', 'O0'], ['I', 'I', '00', '00'], $normalized);
        
        // Hapus tanda baca tapi keep numbers dan letters
        $normalized = preg_replace('/[^A-Z0-9\s]/', '', $normalized);
        
        return $normalized;
    }
    
    /**
     * Hitung similarity dengan algoritma lebih smart untuk OCR
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }
        
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        // Potong ke max 255 karena levenshtein limit
        $str1Short = substr($str1, 0, 255);
        $str2Short = substr($str2, 0, 255);
        
        $maxLen = max(strlen($str1Short), strlen($str2Short));
        
        // Gunakan similar_text untuk lebih robust
        similar_text($str1Short, $str2Short, $percent);
        $similarityPercent = $percent / 100;
        
        // Jika similarity tinggi (>0.7) gunakan itu, kalau tidak gunakan levenshtein
        if ($similarityPercent >= 0.70) {
            return $similarityPercent;
        }
        
        // Fallback ke levenshtein untuk hasil yang sangat berbeda
        $distance = levenshtein($str1Short, $str2Short);
        $levenSimilarity = 1 - ($distance / $maxLen);
        
        // Return rata-rata dari kedua metode untuk yang middling
        return ($similarityPercent + $levenSimilarity) / 2;
    }

    /**
     * Hitung similarity dengan lenient approach - untuk handle OCR noise
     * Menggunakan token-based matching untuk alamat, kelurahan yang sering error
     */
    private function calculateSimilarityLenient(string $str1, string $str2, string $field): float
    {
        // Exact match
        if ($str1 === $str2) {
            return 1.0;
        }
        
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        // UNTUK NIK: OCR sometimes read 0 as O, 1 as I, dll
        if ($field === 'nik') {
            // Store original
            $original = $str1;
            $ocr = $str2;
            
            // Extract digits only
            $origDigits = preg_replace('/\D/', '', $original);
            $ocrDigits = preg_replace('/\D/', '', $ocr);
            
            if (empty($origDigits) || empty($ocrDigits)) {
                return 0.0;
            }
            
            // Compare digit strings
            if ($origDigits === $ocrDigits) {
                return 1.0;
            }
            
            // Allow 1-2 digit errors (OCR misread 1 or 2 digits)
            $distance = levenshtein($origDigits, $ocrDigits);
            $allowed_errors = 2; // Allow max 2 digit mistakes
            if ($distance <= $allowed_errors) {
                return 1.0 - ($distance / strlen($origDigits)) * 0.3; // Slight penalty
            }
            
            return 1.0 - ($distance / max(strlen($origDigits), strlen($ocrDigits)));
        }

        // UNTUK ALAMAT, KELURAHAN: Token-based matching (more lenient)
        if (in_array($field, ['alamat', 'kelurahan', 'kecamatan'])) {
            // Split into tokens (words/numbers)
            $tokens1 = preg_split('/[\s\-\/]+/', $str1, -1, PREG_SPLIT_NO_EMPTY);
            $tokens2 = preg_split('/[\s\-\/]+/', $str2, -1, PREG_SPLIT_NO_EMPTY);
            
            if (empty($tokens1) || empty($tokens2)) {
                return 0.0;
            }
            
            // Count how many tokens match  
            $matched = 0;
            foreach ($tokens1 as $token1) {
                foreach ($tokens2 as $token2) {
                    if ($token1 === $token2) {
                        $matched++;
                        break;
                    }
                    // Allow partial token match (e.g., "veteran" vs "vetevan" = OCR error)
                    $sim = similar_text($token1, $token2, $percent);
                    if ($percent >= 80) {  // 80% match on individual token
                        $matched += 0.8;
                        break;
                    }
                }
            }
            
            // Score: percentage of input tokens that found match in OCR
            $score = $matched / count($tokens1);
            return min($score, 1.0);
        }

        // DEFAULT: Fallback ke similarity calc normal
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }
    
    /**
     * Tentukan validation status berdasarkan RATA-RATA SIMILARITY SCORES
     * SIMPLE & USER-FRIENDLY: Orang awam mudah mengerti
     */
    private function determineValidationStatus(float $score, array $results): string
    {
        // Score-based logic only (3 statuses)
        if ($score >= 90) {
            return 'MATCH';              // ≥90%: Excellent similarity
        } elseif ($score >= 75) {
            return 'PARTIAL_MATCH';      // 75-89%: Good similarity
        } else {
            return 'MISMATCH';           // <75%: Poor similarity
        }
    }
    
    /**
     * Ambil nilai field dari OcrResult
     */
    private function getOcrFieldValue(OcrResult $ocrResult, string $field): ?string
    {
        return match($field) {
            'nik' => $ocrResult->nik,
            'nama' => $ocrResult->nama,
            'tempat_lahir' => $ocrResult->tempat_lahir,
            'tgl_lahir' => $ocrResult->tgl_lahir,
            'alamat' => $ocrResult->alamat,
            'rt_rw' => $ocrResult->rt_rw,
            'kelurahan' => $ocrResult->kelurahan,
            'kecamatan' => $ocrResult->kecamatan,
            'no_kk' => $ocrResult->no_kk,
            default => null,
        };
    }
}
