<?php

namespace Database\Seeders;

use App\Models\Layan;
use Illuminate\Database\Seeder;

class CmsLayanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $layans = [
            [
                'nama'      => 'Layanan Pernikahan',
                'kategori'  => 'pembaruan_dokumen',
                'deskripsi' => 'Layanan pembaruan dokumen kependudukan untuk status pernikahan baru, termasuk penyesuaian data pada KK dan KTP-el.',
                'icon'      => 'fas fa-ring',
                'urutan'    => 1,
            ],
            [
                'nama'      => 'Layanan Perceraian',
                'kategori'  => 'pembaruan_dokumen',
                'deskripsi' => 'Layanan pembaruan data pasca perceraian yang menyesuaikan KK, KTP-el, dan dokumen kependudukan terkait.',
                'icon'      => 'fas fa-gavel',
                'urutan'    => 2,
            ],
            [
                'nama'      => 'Layanan Kelahiran',
                'kategori'  => 'pembaruan_dokumen',
                'deskripsi' => 'Layanan pembaruan dokumen kependudukan untuk pencatatan kelahiran, termasuk penyesuaian KK dan akta kelahiran.',
                'icon'      => 'fas fa-baby',
                'urutan'    => 3,
            ],
            [
                'nama'      => 'Layanan Kematian',
                'kategori'  => 'pembaruan_dokumen',
                'deskripsi' => 'Layanan pembaruan dokumen kependudukan untuk peristiwa kematian, termasuk penyesuaian data keluarga dan dokumen terkait.',
                'icon'      => 'fas fa-cross',
                'urutan'    => 4,
            ],
        ];

        foreach ($layans as $l) {
            Layan::updateOrCreate(
                ['nama' => $l['nama']],
                $l
            );
        }
    }
}
