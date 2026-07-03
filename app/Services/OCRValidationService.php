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

        // Only process KTP documents for validation
        $ktpTypes = ['KTP_SUAMI', 'KTP_ISTRI', 'KTP'];
        if (!in_array($documentType, $ktpTypes)) {
            Log::channel('ocr')->info('Skipping OCR validation for non-KTP document', [
                'document_type' => $documentType,
            ]);
            return null;
        }

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

        // Thresholds - lenient untuk handle OCR errors pada dokumen Indonesia
        $fieldThresholds = [
            'nik' => 0.65,      // NIK: lenient karena OCR sering salah baca digit
            'nama' => 0.60,     // Nama: cukup lenient karena OCR error umum
            'alamat' => 0.50,   // Alamat: token-based matching sudah cukup baik
            'kelurahan' => 0.40, // Kelurahan: OCR sering gagal baca, perlu lenient
            'rt_rw' => 0.80,    // RT/RW: numeric, biasanya akurat
            'kecamatan' => 0.50,  // Kecamatan: cukup lenient
        ];

        foreach ($fieldsToCompare as $field) {
            $inputValue = $inputData[$field] ?? null;
            $ocrValue = $this->getOcrFieldValue($ocrResult, $field);

            // Skip jika kedua field kosong - TIDAK dihitung
            if (empty($inputValue) && empty($ocrValue)) {
                continue;
            }

            $totalFields++;

            // Normalisasi untuk perbandingan
            $inputNormalized = $this->normalize($inputValue);
            $ocrNormalized = $this->normalize($ocrValue);

            // Jika hanya satu yang ada nilainya, similarity = 0
            if (empty($inputNormalized) || empty($ocrNormalized)) {
                $similarity = 0.0;

                // Partial credit untuk field yang kosong di OCR tapi related fields match
                // Ini karena OCR kadang gagal baca отдельный field padahal dokumen valid
                if (!empty($inputNormalized) && empty($ocrNormalized)) {
                    $similarity = $this->calculatePartialCreditForEmptyOcr($field, $inputNormalized, $ocrNormalized, $comparisonResults);
                }
            } else {
                // Untuk NIK, gunakan raw values (tanpa corrupt dari normalize)
                if ($field === 'nik') {
                    $similarity = $this->calculateNikSimilarity($inputValue, $ocrValue);
                } else {
                    // Hitung similarity untuk field lainnya
                    $similarity = $this->calculateSimilarityEnhanced($inputNormalized, $ocrNormalized, $field, $inputValue, $ocrValue);
                }
            }

            $threshold = $fieldThresholds[$field] ?? 0.50;
            $isMatch = $similarity >= $threshold;

            if ($isMatch) {
                $matchedCount++;
            }

            $comparisonResults[$field] = [
                'input'          => $inputValue,
                'ocr'            => $ocrValue,
                'similarity'     => round($similarity, 4),
                'match'          => $isMatch,
                'threshold'      => $threshold,
                'ocr_confidence' => $ocrResult->confidence_scores[$field] ?? null,
            ];
        }

        // Hitung overall match score = rata-rata dari semua similarity values
        $allSimilarities = [];
        foreach ($comparisonResults as $result) {
            $allSimilarities[] = $result['similarity'];
        }

        $overallScore = count($allSimilarities) > 0
            ? round((array_sum($allSimilarities) / count($allSimilarities)) * 100, 2)
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
        // Score-based logic (3 statuses):
        // ≥90% = MATCH (cocok sempurna)
        // 70-89% = PARTIAL_MATCH (sebagian cocok, perlu review)
        // <70% = MISMATCH (tidak cukup cocok)
        if ($score >= 90) {
            return 'MATCH';
        } elseif ($score >= 70) {
            return 'PARTIAL_MATCH';
        } else {
            return 'MISMATCH';
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

    /**
     * Enhanced normalization - handle common OCR errors
     */
    private function normalizeEnhanced(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        $value = strtoupper(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        // Common OCR substitutions for letters
        $letterReplacements = [
            'O' => ['0', 'Q'],  // O bisa terbaca sebagai 0 atau Q
            'I' => ['1', 'L', '!', '|'],  // I bisa terbaca sebagai 1, L
            'S' => ['5', 'Z'],  // S bisa terbaca sebagai 5
            'Z' => ['2', 'S'],  // Z bisa terbaca sebagai 2
            'B' => ['8', 'D'],  // B bisa terbaca sebagai 8
            'G' => ['6', 'Q'],  // G bisa terbaca sebagai 6
        ];

        // Remove common OCR artifacts
        $value = preg_replace('/[|!Il1]/', 'I', $value);  // Pipe, exclamation, I/l/1 confusion
        $value = preg_replace('/[O0Q]/', 'O', $value);  // Zero/O/Q confusion
        $value = preg_replace('/[S5Z]/', 'S', $value);  // S/5/Z confusion

        // Remove special characters
        $value = preg_replace('/[^A-Z0-9\s\-.\/]/', '', $value);

        return $value;
    }

    /**
     * Enhanced similarity calculation - handle OCR errors
     */
    private function calculateSimilarityEnhanced(string $str1, string $str2, string $field, ?string $orig1, ?string $orig2): float
    {
        // Exact match after basic normalization
        if ($str1 === $str2) {
            return 1.0;
        }

        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        // For NIK - most critical field with common OCR errors
        if ($field === 'nik') {
            return $this->calculateNikSimilarity($orig1 ?? $str1, $orig2 ?? $str2);
        }

        // For names - handle common OCR errors on letters
        if ($field === 'nama') {
            return $this->calculateNameSimilarity($str1, $str2);
        }

        // For address-related fields - token based matching
        if (in_array($field, ['alamat', 'kelurahan', 'kecamatan'])) {
            return $this->calculateTokenSimilarity($str1, $str2);
        }

        // For RT/RW - numeric matching
        if ($field === 'rt_rw') {
            return $this->calculateRtRwSimilarity($str1, $str2);
        }

        // Default fallback
        return $this->calculateGenericSimilarity($str1, $str2);
    }

    /**
     * NIK similarity - lenient matching for Indonesian KTP
     * For validation: we want to detect if it's the same person despite OCR errors
     * Key insight: first 4 digits = region code, should match if same person
     */
    private function calculateNikSimilarity(string $nik1, string $nik2): float
    {
        // Extract digits only
        $digits1 = preg_replace('/\D/', '', $nik1);
        $digits2 = preg_replace('/\D/', '', $nik2);

        if (empty($digits1) || empty($digits2)) {
            return 0.0;
        }

        // Exact match
        if ($digits1 === $digits2) {
            return 1.0;
        }

        $len1 = strlen($digits1);
        $len2 = strlen($digits2);

        // Handle length differences - very common in OCR
        if ($len1 !== $len2) {
            $longer = strlen($digits1) > strlen($digits2) ? $digits1 : $digits2;
            $shorter = strlen($digits1) <= strlen($digits2) ? $digits1 : $digits2;
            return $this->calculateSubsetNikSimilarity($shorter, $longer);
        }

        // Same length - count matching digits
        $matching = 0;
        $minLen = min($len1, $len2);

        for ($i = 0; $i < $minLen; $i++) {
            if ($digits1[$i] === $digits2[$i]) {
                $matching++;
            }
        }

        return $matching / $minLen;
    }

    /**
     * Calculate NIK similarity when one is shorter/longer
     * Common case: OCR misses leading/trailing digits
     */
    private function calculateSubsetNikSimilarity(string $shorter, string $longer): float
    {
        $lenShort = strlen($shorter);
        $lenLong = strlen($longer);

        if ($lenShort === 0 || $lenLong === 0) {
            return 0.0;
        }

        // Compare from start - OCR often misses leading zeros
        $prefixMatch = 0;
        for ($i = 0; $i < $lenShort; $i++) {
            if ($shorter[$i] === $longer[$i]) {
                $prefixMatch++;
            } else {
                break; // Stop at first mismatch
            }
        }

        // If most of shorter matches the start of longer, likely same NIK with missing digits
        $matchRatio = $prefixMatch / $lenShort;
        $coverageRatio = $prefixMatch / $lenLong;

        // Case 1: Shorter is prefix of longer (very likely same NIK)
        if ($prefixMatch === $lenShort) {
            // Missing some digits at the end - very common in OCR
            if ($lenShort >= 10) {
                return 0.95;
            } elseif ($lenShort >= 6) {
                return 0.85;
            }
        }

        // Case 2: Check if shorter is contained in longer anywhere
        $foundPos = strpos($longer, $shorter);
        if ($foundPos !== false) {
            return min(0.95, ($lenShort / $lenLong) + 0.3);
        }

        // Case 3: Check if longer is contained in shorter
        if (strpos($shorter, $longer) !== false) {
            return 0.95;
        }

        // Case 4: Partial prefix match with region focus
        $regionMatch = 0;
        for ($i = 0; $i < min(4, $lenShort, $lenLong); $i++) {
            if ($shorter[$i] === $longer[$i]) {
                $regionMatch++;
            }
        }

        if ($regionMatch >= 3 && $prefixMatch / $lenShort >= 0.7) {
            return $prefixMatch / $lenLong;
        }

        // Case 5: General case - Levenshtein-based
        $maxLen = max($lenShort, $lenLong);
        $distance = levenshtein($shorter, substr($longer, 0, $lenShort));
        return max(0, 1 - ($distance / $maxLen));
    }

    /**
     * Name similarity - handle common OCR errors
     * Indonesian names often have 2-4 words, handle partial matches
     */
    private function calculateNameSimilarity(string $name1, string $name2): float
    {
        // Remove common prefixes/titles
        $name1 = preg_replace('/^(DR|DR\.|IR\.|H\.|HAJJI)\s*/i', '', trim($name1));
        $name2 = preg_replace('/^(DR|DR\.|IR\.|H\.|HAJJI)\s*/i', '', trim($name2));

        // Normalize for comparison - remove common OCR artifacts
        $name1Norm = $this->normalizeNameForComparison($name1);
        $name2Norm = $this->normalizeNameForComparison($name2);

        // Check exact match after normalization
        if ($name1Norm === $name2Norm) {
            return 1.0;
        }

        // Split into parts and compare each part
        $parts1 = preg_split('/[\s\-]+/', strtoupper(trim($name1Norm)));
        $parts2 = preg_split('/[\s\-]+/', strtoupper(trim($name2Norm)));

        $parts1 = array_filter($parts1);
        $parts2 = array_filter($parts2);

        if (empty($parts1) || empty($parts2)) {
            return 0.0;
        }

        // Count matching name parts with lenient matching
        $matched = 0;
        $totalParts = count($parts1);

        foreach ($parts1 as $p1) {
            $bestMatch = 0;
            foreach ($parts2 as $p2) {
                // Check for exact match
                if ($p1 === $p2) {
                    $bestMatch = 1.0;
                    break;
                }

                // Check for partial match (handles OCR errors like DEWI vs DEW!, SARTIKA vs SARTIKASS)
                $sim = $this->calculatePartSimilarity($p1, $p2);
                $bestMatch = max($bestMatch, $sim);
            }
            $matched += $bestMatch;
        }

        // Calculate score based on matched parts
        return $matched / $totalParts;
    }

    /**
     * Normalize name for comparison - remove OCR artifacts
     */
    private function normalizeNameForComparison(string $name): string
    {
        // Convert to uppercase
        $name = strtoupper(trim($name));

        // Remove common OCR artifacts: pipes, exclamation marks, quotes, backslashes
        $name = preg_replace('/[|!\"\'\`\\\\\.\,\*]+/', '', $name);

        // Remove duplicate characters (common OCR artifact like "SS" -> "S" or "AA" -> "A")
        $name = preg_replace('/(.)\1{2,}/', '$1', $name);

        // Common character replacements (OCR errors)
        $name = preg_replace('/[O0]/', 'O', $name); // O/0 confusion
        $name = preg_replace('/[I1IL]/', 'I', $name); // I/1/L confusion

        // Remove extra spaces
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    /**
     * Calculate similarity between two name parts
     * Handles common OCR errors like missing characters
     */
    private function calculatePartSimilarity(string $part1, string $part2): float
    {
        if ($part1 === $part2) {
            return 1.0;
        }

        // Check if one is a prefix of the other (handles "SARTIKA" vs "SARTIKASS" or "DEWI" vs "DEW")
        if (strpos($part1, $part2) === 0 || strpos($part2, $part1) === 0) {
            $minLen = min(strlen($part1), strlen($part2));
            $maxLen = max(strlen($part1), strlen($part2));
            // If one is prefix of another, give high score
            return $minLen / $maxLen;
        }

        // Calculate Levenshtein-based similarity
        $maxLen = max(strlen($part1), strlen($part2));
        if ($maxLen === 0) {
            return 0.0;
        }

        // Allow 1 character difference (handles "DEWI" vs "DEW", "SARI" vs "SARIWATI")
        $distance = levenshtein($part1, $part2);
        if ($distance === 1 && $maxLen >= 3) {
            // Only 1 character different - very likely same word
            return 0.85;
        }

        return 1 - ($distance / $maxLen);
    }

    /**
     * Token-based similarity for addresses
     */
    private function calculateTokenSimilarity(string $str1, string $str2): float
    {
        // Pre-process to fix common OCR errors
        $str1 = $this->fixOcrArtifacts($str1);
        $str2 = $this->fixOcrArtifacts($str2);

        // Normalize
        $str1Norm = $this->normalizeAddressForComparison($str1);
        $str2Norm = $this->normalizeAddressForComparison($str2);

        // Exact match
        if ($str1Norm === $str2Norm) {
            return 1.0;
        }

        $tokens1 = preg_split('/[\s\-\/\.]+/', $str1Norm, -1, PREG_SPLIT_NO_EMPTY);
        $tokens2 = preg_split('/[\s\-\/\.]+/', $str2Norm, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($tokens1) || empty($tokens2)) {
            return $this->calculateGenericSimilarity($str1Norm, $str2Norm);
        }

        // Remove common noise words
        $noiseWords = ['GA', 'NO', 'KEL', 'KEC', 'JL', 'RT', 'RW', 'A', 'AN'];
        $tokens1 = array_filter($tokens1, fn($t) => !in_array($t, $noiseWords) && strlen($t) > 1);
        $tokens2 = array_filter($tokens2, fn($t) => !in_array($t, $noiseWords) && strlen($t) > 1);

        // If tokens are empty, check for number match
        if (empty($tokens1) || empty($tokens2)) {
            return $this->checkStreetNumberMatch($str1, $str2);
        }

        $matched = 0;
        $totalTokens = count($tokens1);

        foreach ($tokens1 as $t1) {
            $bestMatch = 0;
            foreach ($tokens2 as $t2) {
                // Exact match
                if ($t1 === $t2) {
                    $bestMatch = 1.0;
                    break;
                }
                // Partial match with lenient threshold
                $sim = $this->calculateGenericSimilarity($t1, $t2);
                if ($sim > $bestMatch) {
                    $bestMatch = $sim;
                }
            }
            if ($bestMatch >= 0.6) {
                $matched += $bestMatch;
            }
        }

        return $matched / $totalTokens;
    }

    /**
     * Fix common OCR artifacts in addresses
     */
    private function fixOcrArtifacts(string $text): string
    {
        // Remove special characters that are OCR noise
        $text = preg_replace('/[|!\"\'\`\*\#\:]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        // Fix common Indonesian address OCR errors
        // S1LIR -> ILIR (S and 1 are OCR artifacts)
        $text = preg_replace('/S1LIR/i', 'ILIR', $text);
        $text = preg_replace('/SILIR/i', 'ILIR', $text);
        // COE -> I (common OCR error)
        $text = str_replace('COE', 'I', $text);
        // ILL -> IL (OCR error)
        $text = preg_replace('/ILL(?![A-Z])/', 'IL', $text);

        return trim($text);
    }

    /**
     * Check if street numbers match
     */
    private function checkStreetNumberMatch(string $str1, string $str2): float
    {
        preg_match_all('/\d+/', $str1, $nums1);
        preg_match_all('/\d+/', $str2, $nums2);

        if (empty($nums1[0]) || empty($nums2[0])) {
            return 0.0;
        }

        $commonNums = array_intersect($nums1[0], $nums2[0]);
        return !empty($commonNums) ? 0.5 : 0.0;
    }

    /**
     * Normalize address for comparison
     */
    private function normalizeAddressForComparison(string $addr): string
    {
        $addr = strtoupper(trim($addr));

        $replacements = [
            '/\bJL\.?\s*/i' => 'JL ',
            '/\bJALAN\s*/i' => 'JL ',
            '/\bKP\.?\s*/i' => 'KP ',
            '/\bKAMPUNG\s*/i' => 'KP ',
            '/\bGG\.?\s*/i' => 'GG ',
            '/\bGANG\s*/i' => 'GG ',
            '/\bNO\.?\s*/i' => 'NO ',
            '/\bNOMOR\s*/i' => 'NO ',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $addr = preg_replace($pattern, $replacement, $addr);
        }

        $addr = preg_replace('/\s+/', ' ', $addr);
        return trim($addr);
    }

    /**
     * Generic similarity using multiple algorithms
     */
    private function calculateGenericSimilarity(string $str1, string $str2): float
    {
        $maxLen = max(strlen($str1), strlen($str2));

        if ($maxLen === 0) {
            return 0.0;
        }

        // Levenshtein-based
        $distance = levenshtein(substr($str1, 0, 255), substr($str2, 0, 255));
        $levenSim = 1 - ($distance / $maxLen);

        // Similar text
        similar_text($str1, $str2, $percent);
        $simTextSim = $percent / 100;

        // Longest common subsequence
        $lcsSim = $this->calculateLcsSimilarity($str1, $str2);

        // Weighted average - LCS is best for partial matches
        return ($levenSim * 0.3 + $simTextSim * 0.3 + $lcsSim * 0.4);
    }

    /**
     * Longest Common Subsequence similarity
     */
    private function calculateLcsSimilarity(string $str1, string $str2): float
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $matrix = [];
        for ($i = 0; $i <= $len1; $i++) {
            $matrix[$i] = [];
            for ($j = 0; $j <= $len2; $j++) {
                if ($i === 0 || $j === 0) {
                    $matrix[$i][$j] = 0;
                } elseif ($str1[$i - 1] === $str2[$j - 1]) {
                    $matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
                } else {
                    $matrix[$i][$j] = max($matrix[$i - 1][$j], $matrix[$i][$j - 1]);
                }
            }
        }

        $lcsLength = $matrix[$len1][$len2];
        return $lcsLength / max($len1, $len2);
    }

    /**
     * Calculate partial credit when OCR field is empty but related fields match
     * This handles cases where OCR fails to read a field but other fields confirm identity
     */
    private function calculatePartialCreditForEmptyOcr(string $field, string $input, string $ocr, array $previousResults): float
    {
        // If critical fields (NIK, Nama) match, give partial credit
        $nikMatch = $previousResults['nik']['match'] ?? false;
        $namaMatch = $previousResults['nama']['match'] ?? false;

        if ($nikMatch && $namaMatch) {
            // NIK and Nama match - OCR likely just failed to read this field
            // Give 40% credit for field that couldn't be read
            return 0.40;
        } elseif ($nikMatch || $namaMatch) {
            // One critical field matches
            return 0.25;
        }

        return 0.0;
    }
}
