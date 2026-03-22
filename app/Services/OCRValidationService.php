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
        
        foreach ($fieldsToCompare as $field) {
            $inputValue = $inputData[$field] ?? null;
            $ocrValue = $this->getOcrFieldValue($ocrResult, $field);
            
            // Skip jika kedua field kosong
            if (empty($inputValue) && empty($ocrValue)) {
                continue;
            }
            
            $totalFields++;
            
            // Normalisasi untuk perbandingan
            $inputNormalized = $this->normalize($inputValue);
            $ocrNormalized = $this->normalize($ocrValue);
            
            // Hitung similarity
            $similarity = $this->calculateSimilarity($inputNormalized, $ocrNormalized);
            $isMatch = $similarity >= 0.90; // 90% threshold
            
            if ($isMatch) {
                $matchedCount++;
            }
            
            $comparisonResults[$field] = [
                'input'      => $inputValue,
                'ocr'        => $ocrValue,
                'similarity' => round($similarity, 4),
                'match'      => $isMatch,
                'confidence' => $ocrResult->confidence_scores[$field] ?? 0,
            ];
        }
        
        // Hitung overall match score
        $overallScore = $totalFields > 0
            ? round(($matchedCount / $totalFields) * 100, 2)
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
     * Normalisasi string untuk perbandingan
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
        
        // Uppercase, hapus spasi berlebih, hapus tanda baca
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = preg_replace('/[^A-Z0-9\s]/', '', $normalized);
        
        return $normalized;
    }
    
    /**
     * Hitung similarity menggunakan Levenshtein distance
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }
        
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        $maxLen = max(strlen($str1), strlen($str2));
        
        // Jika string terlalu panjang, gunakan similar_text
        if ($maxLen > 255) {
            similar_text($str1, $str2, $percent);
            return $percent / 100;
        }
        
        $distance = levenshtein($str1, $str2);
        
        return 1 - ($distance / $maxLen);
    }
    
    /**
     * Tentukan validation status berdasarkan score
     */
    private function determineValidationStatus(float $score, array $results): string
    {
        // Jika NIK tidak match, selalu MISMATCH
        if (isset($results['nik']) && !$results['nik']['match']) {
            return 'MISMATCH';
        }
        
        if ($score >= 95) {
            return 'MATCH';
        } elseif ($score >= 80) {
            return 'PARTIAL_MATCH';
        } elseif ($score >= 60) {
            return 'MANUAL_REVIEW';
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
}
