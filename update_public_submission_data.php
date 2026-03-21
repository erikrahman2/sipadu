<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CaseModel;
use App\Models\PublicSubmission;

echo "\n=== Update Public Submission dengan Data Lengkap ===\n\n";

// Cari case yang punya public submission
$case = CaseModel::with('publicSubmission')->where('id', 5)->first();

if (!$case) {
    echo "❌ Case #5 tidak ditemukan\n";
    exit(1);
}

if (!$case->publicSubmission) {
    echo "❌ Case #5 tidak punya Public Submission\n";
    exit(1);
}

$ps = $case->publicSubmission;

echo "Public Submission ID: {$ps->id}\n";
echo "Tracking Token: {$ps->tracking_token}\n";
echo "NIK: {$ps->nik}\n";
echo "Nama: {$ps->petitioner_name}\n\n";

// Check apakah sudah ada field tambahan
try {
    // Try accessing properties that might not exist
    $checkFields = [
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'rt_rw',
        'kelurahan',
        'kecamatan',
        'no_kk'
    ];
    
    echo "Checking existing fields:\n";
    $existingFields = [];
    $schema = DB::getSchemaBuilder();
    $columns = $schema->getColumnListing('public_submissions');
    
    foreach ($checkFields as $field) {
        if (in_array($field, $columns)) {
            echo "  ✅ $field\n";
            $existingFields[] = $field;
        } else {
            echo "  ❌ $field (belum ada)\n";
        }
    }
    
    echo "\n";
    
    if (count($existingFields) === count($checkFields)) {
        echo "✅ Semua field sudah ada! Updating data...\n\n";
        
        $ps->update([
            'nama_lengkap'    => 'LINDO',
            'tempat_lahir'    => 'PADANG',
            'tanggal_lahir'   => '1976-05-19',
            'alamat'          => 'JL MERDEKA NO 10',
            'rt_rw'           => '001/002',
            'kelurahan'       => 'PAINAN',
            'kecamatan'       => 'IV JURAI',
            'no_kk'           => '1301050102030001',
        ]);
        
        echo "✅ Data berhasil diupdate!\n\n";
        
        // Show updated data
        $ps->refresh();
        echo "Data terbaru:\n";
        echo "  NIK: {$ps->nik}\n";
        echo "  Nama: {$ps->nama_lengkap}\n";
        echo "  Tempat Lahir: {$ps->tempat_lahir}\n";
        echo "  Tgl Lahir: {$ps->tanggal_lahir}\n";
        echo "  Alamat: {$ps->alamat}\n";
        echo "  RT/RW: {$ps->rt_rw}\n";
        echo "  Kelurahan: {$ps->kelurahan}\n";
        echo "  Kecamatan: {$ps->kecamatan}\n";
        echo "  No KK: {$ps->no_kk}\n";
        
    } else {
        echo "\n⚠️  PERLU MIGRATION BARU!\n\n";
        echo "Field yang missing perlu ditambahkan dengan migration.\n";
        echo "Buat migration dengan:\n";
        echo "  php artisan make:migration add_detailed_fields_to_public_submissions\n\n";
        
        echo "Isi migration dengan:\n";
        echo "  \$table->string('nama_lengkap')->nullable()->after('petitioner_name');\n";
        echo "  \$table->string('tempat_lahir', 100)->nullable()->after('nama_lengkap');\n";
        echo "  \$table->date('tanggal_lahir')->nullable()->after('tempat_lahir');\n";
        echo "  \$table->text('alamat')->nullable()->after('tanggal_lahir');\n";
        echo "  \$table->string('rt_rw', 10)->nullable()->after('alamat');\n";
        echo "  \$table->string('kelurahan', 100)->nullable()->after('rt_rw');\n";
        echo "  \$table->string('kecamatan', 100)->nullable()->after('kelurahan');\n";
        echo "  \$table->string('no_kk', 16)->nullable()->after('kecamatan');\n\n";
        
        echo "Lalu jalankan: php artisan migrate\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Selesai ===\n";
