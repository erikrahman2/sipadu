<?php

namespace App\Services;

use App\Models\CmsHomeSection;
use App\Models\CmsAboutSection;
use App\Models\CmsBlogPost;
use Illuminate\Support\Collection;

class ContentService
{
    /**
     * Fetch all home sections that are active, ordered by display_order.
     */
    public function getHomeSections(): Collection
    {
        return CmsHomeSection::where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Fetch about sections by key (hero, visi_misi, layanan, etc.)
     * Falls back to default content if not found.
     */
    public function getAboutSection(string $key): ?CmsAboutSection
    {
        return CmsAboutSection::where('section_key', $key)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Fetch all active about sections, keyed by section_key.
     */
    public function getAllAboutSections(): Collection
    {
        return CmsAboutSection::where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->keyBy('section_key');
    }

    /**
     * Get default hero content (fallback when no CMS data exists).
     */
    public function getDefaultHeroContent(): array
    {
        return [
            'title'       => 'Tentang SiPadu',
            'subtitle'    => 'Sistem Pembaruan Dokumen Pasca Perceraian',
            'description' => 'Sistem digital terintegrasi antara Pengadilan Agama dan Dinas Dukcapil untuk pemeliharaan data pasca perceraian dan cerai mati.',
            'cta_text'    => 'Pelajari Lebih Lanjut',
            'stats'       => [
                ['value' => '2', 'label' => 'Lembaga Terhubung'],
                ['value' => '2', 'label' => 'Instansi Terhubung'],
                ['value' => '1', 'label' => 'SK Penetapan'],
            ],
        ];
    }

    /**
     * Get default visi misi content.
     */
    public function getDefaultVisiMisiContent(): array
    {
        return [
            'visi'   => 'Menjadi sistem terdepan dalam pemeliharaan data pasca perceraian yang akurat, terintegrasi, dan aman.',
            'misi'   => [
                'Memudahkan proses pelaporan dan pemutakhiran data warga negara yang mengalami perubahan status pasca perceraian.',
                'Membantu instansi pemerintah dalam menjaga akurasi dan konsistensi data kependudukan.',
                'Mewujudkan pelayanan publik yang efisien, transparan, dan berbasis teknologi digital.',
            ],
        ];
    }

    /**
     * Get all published blog posts, newest first.
     */
    public function getPublishedPosts(int $limit = 10): Collection
    {
        return CmsBlogPost::published()
            ->with(['author'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get a single published post by slug.
     */
    public function getPostBySlug(string $slug): ?CmsBlogPost
    {
        return CmsBlogPost::published()
            ->where('slug', $slug)
            ->with(['author'])
            ->first();
    }

    /**
     * Get all published posts for archive/listing.
     */
    public function getAllPublishedPosts(): Collection
    {
        return CmsBlogPost::published()
            ->with(['author'])
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * Get default "Our Process" content for about page.
     */
    public function getDefaultProcessSteps(): array
    {
        return [
            [
                'step'   => '01',
                'title'  => 'Pengajuan Permohonan',
                'desc'   => 'Warga mengajukan permohonan pemutakhiran data secara online melalui portal SiPadu atau langsung di kantor instansi terkait.',
                'icon'   => 'document',
            ],
            [
                'step'   => '02',
                'title'  => 'Verifikasi Data oleh PA',
                'desc'   => 'Pengadilan Agama memverifikasi kelengkapan dokumen dan keabsahan putusan perceraian yang diajukan.',
                'icon'   => 'shield-check',
            ],
            [
                'step'   => '03',
                'title'  => 'Sinkronisasi Data',
                'desc'   => 'Data yang telah diverifikasi disinkronisasikan secara otomatis ke sistem Dinas Dukcapil melalui API terintegrasi.',
                'icon'   => 'server',
            ],
            [
                'step'   => '04',
                'title'  => 'Pemutakhiran Dokumen',
                'desc'   => 'Dinas Dukcapil memproses pemutakhiran dokumen kependudukan berdasarkan data yang telah disinkronkan.',
                'icon'   => 'document-check',
            ],
            [
                'step'   => '05',
                'title'  => 'Notifikasi Hasil',
                'desc'   => 'Warga menerima notifikasi hasil pemrosesan dan dapat mengunduh dokumen yang telah diperbarui secara digital.',
                'icon'   => 'bell-alert',
            ],
        ];
    }

    /**
     * Get default service cards content.
     */
    public function getDefaultServices(): array
    {
        return [
            [
                'title'   => 'Layanan Digital',
                'desc'    => 'Proses pengajuan dan verifikasi data dilakukan secara digital tanpa perlu antri atau datang berkali-kali.',
                'icon'    => 'computer-desktop',
            ],
            [
                'title'   => 'Integrasi Instansi',
                'desc'    => 'Terhubung langsung antara Pengadilan Agama dan Dinas Dukcapil untuk efisiensi layanan.',
                'icon'    => 'building-office-2',
            ],
            [
                'title'   => 'Keamanan Data',
                'desc'    => 'Data warga negara dilindungi dengan standar keamanan tinggi dan sistem enkripsi terjamin.',
                'icon'    => 'lock-closed',
            ],
            [
                'title'   => 'Lacak Real-time',
                'desc'    => 'Status pengajuan dapat dilacak secara real-time melalui portal SiPadu kapan saja.',
                'icon'    => 'magnifying-glass',
            ],
        ];
    }

    /**
     * Get default FAQ content.
     */
    public function getDefaultFAQs(): array
    {
        return [
            [
                'q' => 'Apa itu SiPadu?',
                'a' => 'SiPadu adalah Sistem Pembaruan Dokumen Pasca Perceraian yang menghubungkan Pengadilan Agama dan Dinas Dukcapil dalam satu platform digital.',
            ],
            [
                'q' => 'Siapa yang bisa menggunakan layanan ini?',
                'a' => 'Warga negara Indonesia yang telah mengalami perceraian atau cerai mati dan perlu memperbarui dokumen kependudukan.',
            ],
            [
                'q' => 'Bagaimana cara mengajukan?',
                'a' => 'Anda dapat mengajukan langsung melalui portal SiPadu atau datang ke Kantor Pengadilan Agama terdekat dengan membawa dokumen yang diperlukan.',
            ],
            [
                'q' => 'Berapa lama prosesnya?',
                'a' => 'Proses verifikasi dan pemutakhiran biasanya memakan waktu 3-5 hari kerja setelah dokumen lengkap diverifikasi.',
            ],
        ];
    }
}
