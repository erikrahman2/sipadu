<?php

namespace Database\Seeders;

use App\Models\CmsBlogPost;
use App\Models\CmsHomeSection;
use App\Models\CmsAboutSection;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ──────────────────────────────────────────────────────────
        $roleNames = [
            'super_admin',
            'pa_assistant',
            'pa_management',
            'pa_staff',
            'disdukcapil_staff',
        ];

        foreach ($roleNames as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api']);
        }

        // ── Permissions ────────────────────────────────────────────────────
        $permissions = [
            'view cases', 'edit cases', 'delete cases',
            'approve cases', 'validate cases',
            'upload documents', 'download documents',
            'process ocr', 'view ocr results',
            'manage users', 'view audit logs',
            'trigger sync',
            // CMS Halaman Publik
            'view cms', 'create cms', 'edit cms', 'delete cms',
            'manage cms blog', 'manage cms home', 'manage cms about',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
        }

        // ── Assign permissions to roles ────────────────────────────────────
        $role = fn(string $name) => Role::where('name', $name)->where('guard_name', 'web')->first();

        $role('pa_assistant')->syncPermissions([
            'view cases', 'edit cases',
            'upload documents', 'download documents',
            'view ocr results',
        ]);

        $role('pa_management')->syncPermissions([
            'view cases', 'edit cases', 'approve cases',
            'process ocr', 'view ocr results',
            'download documents',
        ]);

        $role('pa_staff')->syncPermissions([
            'view cases', 'upload documents', 'download documents',
            'view ocr results',
            // CMS Halaman Publik – PA Staff adalah editor konten publik
            'view cms', 'create cms', 'edit cms', 'delete cms',
            'manage cms blog', 'manage cms home', 'manage cms about',
        ]);

        $role('disdukcapil_staff')->syncPermissions([
            'view cases', 'validate cases',
            'upload documents', 'download documents',
            'view ocr results',
        ]);

        $role('super_admin')->syncPermissions($permissions);

        // ── Institutions ───────────────────────────────────────────────────
        $pa = Institution::updateOrCreate(
            ['code' => 'PA-PAINAN-01'],
            ['name' => 'Pengadilan Agama Kota Painan', 'type' => 'PA', 'active' => true]
        );

        $disc = Institution::updateOrCreate(
            ['code' => 'DISC-PESSEL-01'],
            ['name' => 'Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Selatan', 'type' => 'DISDUKCAPIL', 'active' => true]
        );

        // ── Super Admin ────────────────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@sipadu.go.id'],
            ['name' => 'Administrator', 'password' => Hash::make('Admin@123456'), 'status' => 'active', 'institution_id' => $pa->id]
        );
        $admin->syncRoles(['super_admin']);

        // ── PA Assistant ───────────────────────────────────────────────────
        $paAsst = User::updateOrCreate(['email' => 'asisten@pa-painan.go.id'], [
            'name' => 'PA Assistant', 'password' => Hash::make('Pass@12345'),
            'status' => 'active', 'institution_id' => $pa->id,
        ]);
        $paAsst->syncRoles(['pa_assistant']);

        // ── PA Management ──────────────────────────────────────────────────
        $paMgmt = User::updateOrCreate(['email' => 'ketua@pa-painan.go.id'], [
            'name' => 'PA Management', 'password' => Hash::make('Pass@12345'),
            'status' => 'active', 'institution_id' => $pa->id,
        ]);
        $paMgmt->syncRoles(['pa_management']);

        // ── PA Staff ───────────────────────────────────────────────────────
        $paStaff = User::updateOrCreate(['email' => 'staf@pa-painan.go.id'], [
            'name' => 'PA Staff', 'password' => Hash::make('Pass@12345'),
            'status' => 'active', 'institution_id' => $pa->id,
        ]);
        $paStaff->syncRoles(['pa_staff']);

        // ── Disdukcapil Staff ──────────────────────────────────────────────
        $discStaff = User::updateOrCreate(['email' => 'petugas@disdukcapil-pessel.go.id'], [
            'name' => 'Disdukcapil Staff', 'password' => Hash::make('Pass@12345'),
            'status' => 'active', 'institution_id' => $disc->id,
        ]);
        $discStaff->syncRoles(['disdukcapil_staff']);

        $this->command->info('Seeded successfully.');
        $this->command->table(
            ['Akun', 'Email', 'Password', 'Role'],
            [
                ['Administrator',     'admin@sipadu.go.id',                'Admin@123456', 'super_admin'],
                ['PA Assistant',      'asisten@pa-painan.go.id',           'Pass@12345',   'pa_assistant'],
                ['PA Management',     'ketua@pa-painan.go.id',             'Pass@12345',   'pa_management'],
                ['PA Staff',          'staf@pa-painan.go.id',              'Pass@12345',   'pa_staff'],
                ['Disdukcapil Staff', 'petugas@disdukcapil-pessel.go.id',  'Pass@12345',   'disdukcapil_staff'],
            ]
        );

        // ── Seed CMS Content ───────────────────────────────────────────
        $this->seedCmsContent();

        // ── Seed OCR Validation Data ───────────────────────────────────
        $this->call(OcrValidationSeeder::class);
    }

    protected function seedCmsContent(): void
    {
        $userId = User::where('email', 'admin@sipadu.go.id')->value('id') ?? 1;

        // ── Home Sections ───────────────────────────────────────────
        $homeSections = [
            [
                'section_key'    => 'hero',
                'title'          => 'Quando Kepastian Hukum, Dokumen Menjadi Kunci',
                'subtitle'       => 'Sistem terintegrasi pembaruan dokumen kependudukan pasca perceraian antara Pengadilan Agama dan Dinas Kependudukan.',
                'content'        => null,
                'cta_label'      => 'Mulai Pengajuan',
                'cta_url'        => route('public.submit.create'),
                'display_order'  => 1,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'section_key'    => 'proses_metodologi',
                'title'          => 'Proses & Metodologi',
                'subtitle'       => 'Kami Membantu Mempercepat Pembaruan Dokumen Anda dengan Sistem yang Terstruktur, Jelas, dan Terpercaya',
                'content'        => 'Langkah 1: Pengajuan & Validasi — Anda dapat langsung mengajukan dokumen melalui platform tanpa perlu datang ke kantor.<br>Langkah 2: Ekstraksi & OCR — Sistem otomatis mengekstraksi data dari dokumen Anda menggunakan teknologi OCR terkini.<br>Langkah 3: Verifikasi PA & Disdukcapil — Dokumen Anda diverifikasi secara berlapis.<br>Langkah 4: Penerbitan & Update — Dokumen Anda berhasil diperbarui di sistem kependudukan.',
                'display_order'  => 2,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'section_key'    => 'fitur_unggulan',
                'title'          => 'Fitur Unggulan',
                'subtitle'       => 'Teknologi terkini untuk proses pembaruan dokumen yang cepat dan aman.',
                'content'        => 'OCR Otomatis, Verifikasi Ganda, Pelacakan Real-Time',
                'display_order'  => 3,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'section_key'    => 'alur_kerja',
                'title'          => 'Alur Kerja Sistem',
                'subtitle'       => 'Proses yang dirancang untuk memudahkan Anda dari awal hingga akhir.',
                'content'        => '1. Buat Akun → 2. Upload Dokumen → 3. Verifikasi → 4. Selesai',
                'display_order'  => 4,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'section_key'    => 'pengguna_sistem',
                'title'          => 'Untuk Siapa?',
                'subtitle'       => 'Sistem ini dirancang untuk melayani berbagai pengguna sesuai dengan peran dan kebutuhannya.',
                'content'        => 'Masyarakat Umum, Pengadilan Agama, Disdukcapil',
                'display_order'  => 5,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'section_key'    => 'statistik',
                'title'          => 'Statistik',
                'subtitle'       => null,
                'content'        => '1,200+ Kasus Diproses, 98% Tingkat Akurasi, 24/7 Layanan Pelacakan, 2.5d Waktu Pemrosesan Rata-rata',
                'display_order'  => 6,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'section_key'    => 'teknologi',
                'title'          => 'Teknologi Terdepan',
                'subtitle'       => 'Dibangun dengan stack teknologi modern dan scalable untuk performa optimal.',
                'content'        => 'Laravel 11, MySQL & Neo4j, Python OCR',
                'display_order'  => 7,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'section_key'    => 'cta_footer',
                'title'          => 'Siap Mulai?',
                'subtitle'       => 'Ajukan pembaruan dokumen Anda sekarang atau lacak pengajuan yang sudah ada dengan cepat dan mudah.',
                'content'        => null,
                'display_order'  => 8,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
        ];

        foreach ($homeSections as $section) {
            CmsHomeSection::updateOrCreate(
                ['section_key' => $section['section_key']],
                $section
            );
        }

        // ── About Sections ──────────────────────────────────────────
        $aboutSections = [
            [
                'section_key'   => 'tentang_sipadu',
                'title'         => 'Apa Itu SiPadu?',
                'content'       => '<strong>Sistem Pembaruan Dokumen Pasca Perceraian (SiPadu)</strong> adalah platform terintegrasi yang menghubungkan Pengadilan Agama dan Dinas Kependudukan dan Pencatatan Sipil (Disdukcapil) untuk memproses pembaruan dokumen kependudukan secara digital.<br><br>Kami bekerja untuk memastikan bahwa setiap warga mendapatkan akses mudah dan aman dalam memperbarui dokumen pribadi mereka pasca perceraian, tanpa harus mengurus dokumen fisik atau bolak-balik ke kantor.',
                'display_order' => 1,
                'is_active'     => true,
                'updated_by'    => $userId,
            ],
            [
                'section_key'   => 'visi_misi',
                'title'         => 'Visi & Misi',
                'content'       => '<strong>Visi:</strong> Menjadi sistem terpadu yang memberikan kepastian hukum, kecepatan pemrosesan, dan transparansi penuh dalam pembaruan dokumen kependudukan pasca perceraian.<br><br><strong>Misi:</strong> Menghubungkan institusi pemerintah, mempercepat proses administratif, dan memberikan pengalaman terbaik kepada masyarakat dalam mengurus pembaruan dokumen.',
                'display_order' => 2,
                'is_active'     => true,
                'updated_by'    => $userId,
            ],
            [
                'section_key'   => 'fitur_keunggulan',
                'title'         => 'Fitur & Keunggulan',
                'content'       => 'Pengajuan Tanpa Akun — Masyarakat dapat mengajukan tanpa perlu membuat akun, cukup dengan NIK dan nomor WhatsApp.<br><br>OCR Otomatis — Ekstraksi data dari dokumen secara otomatis dengan teknologi OCR terkini.<br><br>Verifikasi Berlapis — Setiap dokumen diverifikasi oleh PA dan Disdukcapil untuk kepastian hukum.<br><br>Pelacakan Real-Time — Lacak status pengajuan kapan saja melalui token yang dikirim via WhatsApp.',
                'display_order' => 3,
                'is_active'     => true,
                'updated_by'    => $userId,
            ],
            [
                'section_key'   => 'institusi_pendukung',
                'title'         => 'Institusi Pendukung',
                'content'       => '<strong>Pengadilan Agama:</strong> Menerbitkan putusan dan surat keterangan perceraian, mengelola kasus perceraian, dan verifikasi awal dokumen.<br><br><strong>Disdukcapil:</strong> Melakukan validasi data kependudukan, pembaruan data di sistem PIK, dan penerbitan dokumen resmi.',
                'display_order' => 4,
                'is_active'     => true,
                'updated_by'    => $userId,
            ],
        ];

        foreach ($aboutSections as $section) {
            CmsAboutSection::updateOrCreate(
                ['section_key' => $section['section_key']],
                $section
            );
        }

        // ── Blog Header (hero subtitle) ───────────────────────────
        CmsHomeSection::updateOrCreate(
            ['section_key' => 'blog_header'],
            [
                'title'         => 'Berita & Pengumuman',
                'subtitle'      => 'Tips, trik, dan panduan terkini seputar pembaruan dokumen',
                'display_order' => 0,
                'is_active'     => true,
                'updated_by'    => $userId,
            ]
        );

        // ── Home Page SEO ────────────────────────────────────────
        CmsHomeSection::updateOrCreate(
            ['section_key' => 'home_seo'],
            [
                'title'         => 'Sistem Pembaruan Dokumen Pasca Perceraian (SiPadu)',
                'content'       => 'Platform terintegrasi untuk pembaruan dokumen kependudukan pasca perceraian. Terhubung antara Pengadilan Agama dan Disdukcapil.',
                'display_order' => 0,
                'is_active'     => true,
                'updated_by'    => $userId,
            ]
        );

        // ── Blog Posts ──────────────────────────────────────────────
        $blogPosts = [
            [
                'title'         => 'Peluncuran Sistem SiPadu Versi 1.0',
                'slug'          => 'peluncuran-sistem-sipadu-versi-1',
                'excerpt'       => 'Platform terintegrasi pembaruan dokumen kependudukan pasca perceraian telah resmi diluncurkan dengan fitur OCR otomatis, verifikasi berlapis, dan pelacakan real-time.',
                'content'       => 'Platform terintegrasi pembaruan dokumen kependudukan pasca perceraian telah resmi diluncurkan dengan fitur OCR otomatis, verifikasi berlapis, dan pelacakan real-time.<br><br>Sistem SiPadu menghubungkan Pengadilan Agama dan Dinas Kependudukan dan Pencatatan Sipil (Disdukcapil) dalam satu platform digital yang memudahkan masyarakat dalam mengurus pembaruan dokumen kependudukan pasca perceraian.<br><br>Dengan teknologi OCR (Optical Character Recognition), sistem mampu mengekstrak data dari dokumen secara otomatis sehingga proses verifikasi menjadi lebih cepat dan akurat.',
                'cover_image'   => null,
                'author_name'   => 'Admin SiPadu',
                'status'        => 'PUBLISHED',
                'published_at'  => now()->subDays(30),
                'author_id'     => $userId,
                'updated_by'    => $userId,
            ],
            [
                'title'         => 'Panduan Lengkap Pengajuan Dokumen Tanpa Akun',
                'slug'          => 'panduan-pengajuan-dokumen-tanpa-akun',
                'excerpt'       => 'Sekarang Anda dapat mengajukan pembaruan dokumen tanpa perlu membuat akun. Cukup dengan NIK dan nomor WhatsApp, proses sudah bisa dilakukan.',
                'content'       => 'Sekarang Anda dapat mengajukan pembaruan dokumen tanpa perlu membuat akun. Cukup dengan NIK dan nomor WhatsApp, proses sudah bisa dilakukan.<br><br><strong>Langkah-langkah pengajuan:</strong><br>1. Buka halaman pengajuan SiPadu<br>2. Isi formulir dengan data diri dan NIK<br>3. Unggah dokumen yang diperlukan<br>4. Sistem akan melakukan proses OCR otomatis<br>5. Anda akan menerima token tracking via WhatsApp<br>6. Lacak status pengajuan kapan saja menggunakan token tersebut.',
                'cover_image'   => null,
                'author_name'   => 'Admin SiPadu',
                'status'        => 'PUBLISHED',
                'published_at'  => now()->subDays(15),
                'author_id'     => $userId,
                'updated_by'    => $userId,
            ],
            [
                'title'         => 'Keamanan Data dan Privasi Pengguna di SiPadu',
                'slug'          => 'keamanan-data-privasi-sipadu',
                'excerpt'       => 'Sistem SiPadu mengutamakan keamanan dan privasi data pengguna. Setiap data dienkripsi dan hanya diakses oleh petugas berwenang.',
                'content'       => 'Sistem SiPadu mengutamakan keamanan dan privasi data pengguna. Setiap data dienkripsi dan hanya diakses oleh petugas berwenang.<br><br>Kami menggunakan standar keamanan tinggi untuk melindungi data pribadi Anda. Data yang Anda masukkan hanya digunakan untuk proses verifikasi dan pembaruan dokumen kependudukan.<br><br>Semua transaksi dilindungi dengan enkripsi SSL/TLS dan data disimpan di server yang aman.',
                'cover_image'   => null,
                'author_name'   => 'Admin SiPadu',
                'status'        => 'PUBLISHED',
                'published_at'  => now()->subDays(5),
                'author_id'     => $userId,
                'updated_by'    => $userId,
            ],
        ];

        foreach ($blogPosts as $post) {
            CmsBlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                $post
            );
        }

        $this->command->info('CMS content seeded successfully.');
    }
}