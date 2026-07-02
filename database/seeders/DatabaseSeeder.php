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

        // ── Seed CMS Layanan ─────────────────────────────────────────────
        $this->call(CmsLayanSeeder::class);

    }

    protected function seedCmsContent(): void
    {
        $userId = User::where('email', 'admin@sipadu.go.id')->value('id') ?? 1;

        // ── Home Sections ───────────────────────────────────────────
        $homeSections = [
            [
                'content_type'   => 'home_seo',
                'title'          => 'SiPadu - Kerja Sama PA Painan & Disdukcapil Pessel',
                'subtitle'       => null,
                'content'        => 'Sistem Pembaruan Dokumen Pasca Perceraian hasil kerja sama Pengadilan Agama Painan dan Disdukcapil Kabupaten Pesisir Selatan.',
                'display_order'  => 0,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'content_type'   => 'hero',
                'title'          => 'Menjamin Kepastian Hukum, Dokumen Menjadi Kunci',
                'subtitle'       => 'Sistem terintegrasi pembaruan dokumen kependudukan pasca perceraian — hasil kerja sama Pengadilan Agama Painan dan Disdukcapil Kabupaten Pesisir Selatan.',
                'content'        => null,
                'cta_label'      => 'Mulai Pengajuan',
                'cta_url'        => route('public.submit.create'),
                'secondary_cta_url' => route('services'),
                'display_order'  => 1,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'content_type'   => 'proses_metodologi',
                'title'          => 'Proses',
                'subtitle'       => 'Bagaimana cara kerjanya?',
                'content'        => null,
                'display_order'  => 2,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'content_type'   => 'fitur_unggulan',
                'title'          => 'Layanan Kami',
                'subtitle'       => 'Lihat detail layanan yang tersedia melalui kerja sama Pengadilan Agama Painan dan Disdukcapil Kabupaten Pesisir Selatan.',
                'content'        => null,
                'display_order'  => 3,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'content_type'   => 'statistik',
                'title'          => 'Statistik',
                'subtitle'       => "200+|Kasus Diproses\n98%|Tingkat Akurasi\n24/7|Layanan Aktif\n2.5d|Waktu Pemrosesan Rata-rata",
                'content'        => null,
                'display_order'  => 4,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
            [
                'content_type'   => 'cta_footer',
                'title'          => 'Siap untuk memulai?',
                'subtitle'       => 'Gunakan platform SiPadu untuk memproses pembaruan dokumen kependudukan pasca perceraian — terintegrasi, transparan, dan dapat dilacak.',
                'content'        => null,
                'cta_label'      => 'Buat Pengajuan Sekarang',
                'cta_url'        => route('public.submit.create'),
                'display_order'  => 6,
                'is_active'      => true,
                'updated_by'     => $userId,
            ],
        ];

        foreach ($homeSections as $section) {
            CmsHomeSection::updateOrCreate(
                ['content_type' => $section['content_type']],
                $section
            );
        }

        // ── About Sections ──────────────────────────────────────────
        $aboutSections = [
            [
                'content_type'  => 'tentang_sipadu',
                'title'         => 'Apa Itu SiPadu?',
                'content'       => '<strong>Sistem Pembaruan Dokumen Pasca Perceraian (SiPadu)</strong> adalah platform terintegrasi yang menghubungkan Pengadilan Agama dan Dinas Kependudukan dan Pencatatan Sipil (Disdukcapil) untuk memproses pembaruan dokumen kependudukan secara digital.<br><br>Kami bekerja untuk memastikan bahwa setiap warga mendapatkan akses mudah dan aman dalam memperbarui dokumen pribadinya pasca perceraian, tanpa harus mengurus dokumen fisik atau bolak-balik ke kantor.',
                'display_order' => 1,
                'is_active'     => true,
                'updated_by'    => $userId,
            ],
            [
                'content_type'  => 'visi_misi',
                'title'         => 'Visi & Misi',
                'content'       => '<strong>Visi:</strong> Menjadi sistem terpadu yang memberikan kepastian hukum, kecepatan pemrosesan, dan transparansi penuh dalam pembaruan dokumen kependudukan pasca perceraian.<br><br><strong>Misi:</strong> Menghubungkan institusi pemerintah, mempercepat proses administratif, dan memberikan pengalaman terbaik kepada masyarakat dalam mengurus pembaruan dokumen.',
                'display_order' => 2,
                'is_active'     => true,
                'updated_by'    => $userId,
            ],
            [
                'content_type'  => 'institusi_kerja-sama',
                'title'         => 'Instansi & Kerja Sama',
                'content'       => '<strong>Pengadilan Agama Painan</strong> didirikan berdasarkan Peraturan Pemerintah Nomor 45 Tahun 1957 tentang pembentukan Peradilan Agama di luar Jawa dan Madura, yang sebelumnya dikenal sebagai Mahkamah Syar\'iyah. Pembentukan ini dilakukan untuk memberikan kepastian hukum terhadap keberadaan Peradilan Agama setelah pengadilan adat dan swapraja dihapuskan. Ketentuan tersebut kemudian diperkuat melalui Penetapan Menteri Agama Nomor 58 Tahun 1957 yang mengatur pembentukan Pengadilan Agama di wilayah Sumatera, termasuk Painan.<br><br><strong>Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Selatan</strong> merupakan perangkat daerah di lingkungan Pemerintah Kabupaten Pesisir Selatan yang bertugas menyelenggarakan urusan pemerintahan di bidang administrasi kependudukan dan pencatatan sipil. Disdukcapil Pessel terus melakukan pembaruan layanan melalui pengelolaan data kependudukan, penerbitan Nomor Induk Kependudukan (NIK), penerbitan dokumen kependudukan, serta penerapan Kartu Tanda Penduduk Elektronik (KTP-el). Berbagai layanan yang diberikan meliputi penerbitan Kartu Keluarga (KK), KTP elektronik, Akta Kelahiran, Akta Kematian, Akta Perkawinan, Akta Perceraian, dan layanan Identitas Kependudukan Digital (IKD).<br><br><strong>Kerja Sama Ini</strong> bertujuan untuk menghadirkan layanan pembaruan dokumen kependudukan yang terintegrasi, efisien, dan mudah diakses oleh masyarakat Kabupaten Pesisir Selatan — menghubungkan putusan pengadilan dengan pemutakhiran data kependudukan dalam satu ekosistem digital.',
                'display_order' => 4,
                'is_active'     => true,
                'updated_by'    => $userId,
            ],
            [
                'content_type'  => 'fitur_keunggulan',
                'title'         => 'Fitur & Keunggulan',
                'content'       => 'Pengajuan Tanpa Akun — Masyarakat dapat mengajukan tanpa perlu membuat akun, cukup dengan NIK dan nomor WhatsApp.<br><br>OCR Otomatis — Ekstraksi data dari dokumen secara otomatis dengan teknologi OCR terkini.<br><br>Verifikasi Berlapis — Setiap dokumen diverifikasi oleh PA dan Disdukcapil untuk kepastian hukum.<br><br>Pelacakan Real-Time — Lacak status pengajuan kapan saja melalui token yang dikirim via WhatsApp.',
                'display_order' => 3,
                'is_active'     => true,
                'updated_by'    => $userId,
            ],
            [
                'content_type'  => 'institusi_pendukung',
                'title'         => 'Instansi Pendukung',
                'content'       => '<strong>Pengadilan Agama Painan:</strong> Menerbitkan putusan dan surat keterangan perceraian, mengelola kasus perceraian, dan verifikasi awal dokumen.<br><br><strong>Disdukcapil Pessel:</strong> Melakukan validasi data kependudukan, pembaruan data di sistem PIK, dan penerbitan dokumen resmi.',
                'display_order' => 5,
                'is_active'     => true,
                'updated_by'    => $userId,
            ],
        ];

        foreach ($aboutSections as $section) {
            CmsAboutSection::updateOrCreate(
                ['content_type' => $section['content_type']],
                $section
            );
        }

        // ── Blog Header (hero subtitle) for Berita page
        CmsHomeSection::updateOrCreate(
            ['content_type' => 'blog_header'],
            [
                'title'         => 'Berita',
                'subtitle'      => 'Berita & Pengumuman Terbaru',
                'display_order' => 5,
                'is_active'     => true,
                'updated_by'    => $userId,
            ]
        );

        // ── Hero Banner Berita (search bar hero) for Berita page
        CmsHomeSection::updateOrCreate(
            ['content_type' => 'hero_banner'],
            [
                'title'         => 'Berita & Pengumuman',
                'subtitle'      => 'Informasi terbaru seputar kerja sama Pengadilan Agama Painan dan Disdukcapil Kabupaten Pesisir Selatan, pengumuman layanan SiPadu, dan update sistem pembaruan dokumen pasca perceraian.',
                'content'       => '#0D1F08',
                'cta_label'     => 'Cari berita...',
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
                'excerpt'       => 'Platform terintegrasi pembaruan dokumen kependudukan hasil kerja sama Pengadilan Agama Painan dan Disdukcapil Kabupaten Pesisir Selatan resmi diluncurkan.',
                'content'       => 'Platform terintegrasi pembaruan dokumen kependudukan pasca perceraian telah resmi diluncurkan di Kabupaten Pesisir Selatan. Sistem SiPadu merupakan implementasi kerja sama antara <strong>Pengadilan Agama Painan</strong> dan <strong>Disdukcapil Kabupaten Pesisir Selatan</strong>.<br><br><strong>Pengadilan Agama Painan</strong> dibentuk berdasarkan Peraturan Pemerintah Nomor 45 Tahun 1957 tentang pembentukan Peradilan Agama di luar Jawa dan Madura, yang sebelumnya dikenal sebagai Mahkamah Syar\'iyah. Ketentuan tersebut diperkuat dengan Penetapan Menteri Agama Nomor 58 Tahun 1957.<br><br><strong>Disdukcapil Pessel</strong> menyelenggarakan berbagai layanan administrasi kependudukan — termasuk penerbitan KK, KTP-el, Akta Kelahiran, Akta Perkawinan, dan Akta Perceraian — yang kini terintegrasi secara digital melalui SiPadu.<br><br>Dengan teknologi OCR, sistem mampu mengekstrak data dari dokumen putusan secara otomatis sehingga verifikasi lebih cepat dan akurat.',
                'cover_image'   => null,
                'author_name'   => 'Admin SiPadu',
                'status'        => 'PUBLISHED',
                'published_at'  => now()->subDays(30),
                'author_id'     => $userId,
                'updated_by'    => $userId,
            ],
            [
                'title'         => 'Panduan Lengkap Pengajuan Pembaruan Dokumen',
                'slug'          => 'panduan-pengajuan-dokumen-tanpa-akun',
                'excerpt'       => 'Masyarakat Pesisir Selatan kini dapat mengajukan pembaruan dokumen pasca perceraian tanpa akun. Cukup dengan NIK dan WhatsApp, proses berjalan otomatis.',
                'content'       => 'Masyarakat Kabupaten Pesisir Selatan kini dapat mengajukan pembaruan dokumen kependudukan pasca perceraian tanpa harus membuat akun terlebih dahulu. Cukup dengan NIK dan nomor WhatsApp, proses sudah bisa dilakukan.<br><br><strong>Langkah-langkah pengajuan:</strong><br>1. Buka halaman pengajuan SiPadu<br>2. Isi formulir dengan data diri, NIK, dan data putusan PA Painan<br>3. Unggah dokumen putusan perceraian, KTP, dan Kartu Keluarga<br>4. Petugas PA Painan memverifikasi dokumen (1-2 hari kerja)<br>5. Disdukcapil Pessel melakukan pembaruan data (2-3 hari kerja)<br>6. Anda akan menerima token tracking via WhatsApp untuk memantau status<br><br><strong>Layanan yang terhubung:</strong><br>• Pembaruan Kartu Keluarga (KK)<br>• Pembaruan KTP-el<br>• Penerbitan Akta Perceraian',
                'cover_image'   => null,
                'author_name'   => 'Admin SiPadu',
                'status'        => 'PUBLISHED',
                'published_at'  => now()->subDays(15),
                'author_id'     => $userId,
                'updated_by'    => $userId,
            ],
            [
                'title'         => 'Mengenal Pengadilan Agama Painan dan Disdukcapil Pessel',
                'slug'          => 'mengenal-pa-painan-dan-disdukcapil-pessel',
                'excerpt'       => 'Kerja sama SiPadu lahir dari sinergi dua instansi: Pengadilan Agama Painan (bentukan PP 45/1957) dan Disdukcapil Kabupaten Pesisir Selatan.',
                'content'       => 'SiPadu merupakan jembatan digital antara dua institusi pemerintah Kabupaten Pesisir Selatan.<br><br><strong>Pengadilan Agama Painan</strong> didirikan berdasarkan Peraturan Pemerintah Nomor 45 Tahun 1957 tentang pembentukan Peradilan Agama di luar Jawa dan Madura. Sebelumnya lembaga ini dikenal sebagai Mahkamah Syar\'iyah. Pembentukan ini memberikan kepastian hukum setelah pengadilan adat dan swapraja dihapuskan. Ketentuan tersebut diperkuat melalui Penetapan Menteri Agama Nomor 58 Tahun 1957.<br><br><strong>Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Selatan</strong> merupakan perangkat daerah yang menyelenggarakan urusan administrasi kependudukan dan pencatatan sipil. Disdukcapil Pessel terus berinovasi dengan layanan digital, termasuk penerbitan KTP-el, KK, dan berbagai akta catatan sipil.<br><br><strong>Kerja Sama SiPadu</strong> memungkinkan putusan perceraian dari PA Painan langsung terhubung ke data kependudukan di Disdukcapil Pessel, sehingga warga tidak perlu bolak-balik ke kantor untuk mengurus pembaruan KK dan KTP-el.',
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