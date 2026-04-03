<?php

use Illuminate\Support\Facades\Route;
use App\Models\PublicSubmission;
use Illuminate\Support\Str;

Route::prefix('debug')->name('debug.')->group(function () {
    
    Route::get('/test-insert', function () {
        try {
            $submission = PublicSubmission::create([
                'tracking_token'    => 'TEST-' . strtoupper(Str::random(20)),
                'nik_suami'         => '1234567890123456',
                'nama_suami'        => 'Test Suami',
                'alamat_suami'      => 'Test Alamat Suami',
                'rt_rw_suami'       => '01/02',
                'kelurahan_suami'   => 'Test Kelurahan',
                'kecamatan_suami'   => 'Test Kecamatan',
                'nik_istri'         => '9876543210987654',
                'nama_istri'        => 'Test Istri',
                'alamat_istri'      => 'Test Alamat Istri',
                'rt_rw_istri'       => '03/04',
                'kelurahan_istri'   => 'Test Kelurahan',
                'kecamatan_istri'   => 'Test Kecamatan',
                'phone_wa'          => '812345678901',
                'institution_id'    => 1,
                'status'            => 'PENDING',
                'is_active'         => true,
            ]);
            
            return [
                'success' => true,
                'submission' => $submission->fresh(),
                'count' => PublicSubmission::count(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    });
    
});
