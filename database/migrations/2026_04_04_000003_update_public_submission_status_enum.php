<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, modify the ENUM to add SUBMITTED
        DB::statement("ALTER TABLE public_submissions MODIFY COLUMN status ENUM('PENDING', 'REVIEWING', 'SUBMITTED', 'WAITING_OCR', 'APPROVED', 'REJECTED', 'COMPLETED') NOT NULL DEFAULT 'SUBMITTED'");
        
        // Now update any existing WAITING_OCR status to SUBMITTED
        DB::statement("UPDATE public_submissions SET status = 'SUBMITTED' WHERE status = 'WAITING_OCR'");
        
        // Finally, remove WAITING_OCR from ENUM
        DB::statement("ALTER TABLE public_submissions MODIFY COLUMN status ENUM('PENDING', 'REVIEWING', 'SUBMITTED', 'APPROVED', 'REJECTED', 'COMPLETED') NOT NULL DEFAULT 'SUBMITTED'");
    }

    public function down(): void
    {
        // Revert to original ENUM
        DB::statement("ALTER TABLE public_submissions MODIFY COLUMN status ENUM('PENDING','REVIEWING','WAITING_OCR','APPROVED','REJECTED','COMPLETED') NOT NULL DEFAULT 'PENDING'");
    }
};
