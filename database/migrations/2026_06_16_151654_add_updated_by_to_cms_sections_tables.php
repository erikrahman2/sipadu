<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Columns + FK already exist from 2026_06_15_180757 migration.
        // The 001707 pending migration also added this column; ensure no conflict.
    }

    public function down(): void
    {
        // noop — already handled by 2026_06_15_180757 down()
    }
};
