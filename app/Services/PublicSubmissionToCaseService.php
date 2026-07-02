<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\PublicSubmission;
use App\Services\DocumentTypeMapper;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk mengkonversi PublicSubmission menjadi Case
 * untuk diproses di PA Management / Disdukcapil.
 */
class PublicSubmissionToCaseService
{
    /**
     * Buat case baru dari public submission.  
     * Digunakan ketika PA Assistant/PA Management ingin membuat case resmi dari pengajuan publik.
     *
     * @param PublicSubmission $submission
     * @param int $submitterId ID user yang membuat case (bisa PA Assistant atau PA Management)
     * @return CaseModel
     */
    public function convertToCase(PublicSubmission $submission, int $submitterId): CaseModel
    {
        return DB::transaction(function () use ($submission, $submitterId) {
            
            $case = CaseModel::create([
                'case_number'           => null, // Auto-generate di boot
                'tracking_token'        => null, // Auto-generate di boot
                'submitter_id'          => $submitterId,
                'public_submission_id'  => $submission->id,
                'source_type'           => 'public',
                
                // Petitioner (dari field suami atau istri)
                'petitioner_nik'        => $submission->nik_suami,
                'petitioner_name'       => $submission->nama_suami,
                'petitioner_phone'      => $submission->phone_wa,
                'petitioner_alamat'     => $submission->alamat_suami,
                'petitioner_rt_rw'      => $submission->rt_rw_suami,
                'petitioner_kelurahan'  => $submission->kelurahan_suami,
                'petitioner_kecamatan'  => $submission->kecamatan_suami,
                
                // Spouse (dari field istri)
                'spouse_nik'            => $submission->nik_istri,
                'spouse_name'           => $submission->nama_istri,
                'spouse_alamat'         => $submission->alamat_istri,
                'spouse_rt_rw'          => $submission->rt_rw_istri,
                'spouse_kelurahan'      => $submission->kelurahan_istri,
                'spouse_kecamatan'      => $submission->kecamatan_istri,
                
                // Perceraian & Institusi
                'cerai_type'         => $submission->cerai_type,
                'divorce_date'       => $submission->divorce_date,
                'verdict_number'     => $submission->verdict_number,
                'institution_id'     => $submission->institution_id,
                'notes'              => $submission->notes,
                
                'status'                => 'SUBMITTED',
            ]);

            // Link case ke public submission (untuk referensi)
            $submission->update([
                'case_id' => $case->id,
                'status'  => 'APPROVED', // Mark submission as approved/processed
            ]);

            // Copy dokumen dari public submission ke case
            $this->copyDocuments($submission, $case);

            return $case;
        });
    }

    /**
     * Copy dokumen dari public submission ke case documents.
     *
     * @param PublicSubmission $submission
     * @param CaseModel $case
     */
    private function copyDocuments(PublicSubmission $submission, CaseModel $case): void
    {
        foreach ($submission->documents as $pubDoc) {
            $docType = DocumentTypeMapper::toCaseType($pubDoc->document_type);

            \App\Models\Document::create([
                'case_id'            => $case->id,
                'document_type'      => $docType,
                'original_filename'  => $pubDoc->original_filename,
                'stored_path'        => $pubDoc->stored_path,
                'file_size'          => $pubDoc->file_size,
                'mime_type'          => $pubDoc->mime_type,
            ]);
        }
    }

    /**
     * Check if a public submission can be converted to case.
     */
    public function canConvert(PublicSubmission $submission): bool
    {
        // Hanya submission yang APPROVED atau dengan case_id yang valid
        return $submission->status === 'APPROVED' || $submission->case_id !== null;
    }
}
