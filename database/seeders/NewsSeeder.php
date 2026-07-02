<?php

namespace Database\Seeders;

use App\Models\CmsBlogPost;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Sources:
     * - PA Painan: https://pa-painan.go.id/category/berita-terbaru/
     * - Disdukcapil: https://disdukcapil.pesisirselatankab.go.id/news
     */
    public function run(): void
    {
        $news = [
            [
                'title'       => 'Gelar Rapat Umum, Ketua PA Painan Tekankan Sinergitas dan Integritas Aparatur',
                'category_name' => 'Informasi',
                'excerpt'     => 'Rapat umum berfokus pada evaluasi kinerja, monitoring capaian target program kerja, dan strategi peningkatan mutu pelayanan bagi masyarakat pencari keadilan di Pesisir Selatan.',
                'content'     => "Painan | Jumat, 12 Juni 2026\n\nBertempat di Ruang Pertemuan Utama, jajaran aparatur Pengadilan Agama Painan menyelenggarakan Rapat Umum bersama Ketua Pengadilan Agama Painan YM. Milda Sukmawati, S.H.I.\n\nRapat ini dihadiri oleh para Hakim, Panitera, Sekretaris, serta seluruh pegawai Pengadilan Agama Painan.\n\nRapat umum kali ini berfokus pada evaluasi kinerja, monitoring capaian target program kerja, serta pembahasan strategi peningkatan mutu pelayanan bagi masyarakat pencari keadilan di wilayah Kabupaten Pesisir Selatan.\n\nDalam pembinaannya, Ketua Pengadilan Agama Painan menekankan pentingnya menjaga integritas, meningkatkan kedisiplinan, serta terus berinovasi dalam memberikan pelayanan yang prima, transparan, dan akuntabel.\n\nMelalui rapat ini, diharapkan seluruh lini kepaniteraan dan kesekretariatan dapat terus bersinergi demi mewujudkan badan peradilan yang agung dan modern.",
                'author_name' => 'Humas PA Painan',
                'status'       => 'PUBLISHED',
                'published_at'=> '2026-06-12 00:00:00',
            ],
            [
                'title'       => 'Dukungan dan Rekomendasi Instansi Eksternal Perkuat Pengusulan Kenaikan Kelas PA Painan Menjadi Kelas 1B',
                'category_name' => 'Pengumuman',
                'excerpt'    => 'Berbagai instansi eksternal menyampaikan dukungan dan rekomendasi untuk pengusulan kenaikan kelas Pengadilan Agama Painan menjadi Kelas 1B.',
                'content'    => "Painan — Berbagai dukungan dan rekomendasi dari instansi eksternal memperkuat pengusulan Kenaikan Kelas Pengadilan Agama Painan menjadi Kelas 1B.\n\nHal ini mencerminkan kepercayaan dan apresiasi terhadap kinerja PA Painan dalam memberikan pelayanan prima kepada masyarakat pencari keadilan.\n\nPengadilan Agama Painan terus berupaya meningkatkan mutu layanan, memperbaiki infrastruktur, serta memberdayakan sumber daya manusia untuk meraih standar kelas yang lebih tinggi.",
                'author_name' => 'Humas PA Painan',
                'status'       => 'PUBLISHED',
                'published_at'=> '2026-06-12 00:00:00',
            ],
            [
                'title'       => 'Pemeriksaan Setempat di Indrapura, PA Painan Pastikan Kejelasan Objek Sengketa',
                'category_name' => 'Informasi',
                'excerpt'    => "Ketua PA Painan memimpin tim Majelis Hakim melakukan pemeriksaan setempat di Indrapura, Kabupaten Pesisir Selatan untuk memastikan kejelasan objek sengketa.",
                'content'    => "Painan | 10 Juni 2026\n\nPA Painan lakukan pemeriksaan setempat di Indrapura, Kabupaten Pesisir Selatan. Ketua Milda Sukmawati pimpin tim Majelis Hakim.\n\nKegiatan ini bertujuan memastikan kejelasan objek sengketa dalam perkara yang sedang ditangani. Tim mengamati langsung kondisi lapangan untuk melengkapi pembuktian fakta.\n\nPemeriksaan setempat (locaal onderzoek) merupakan kewenangan majelis hakim untuk langsung memeriksa objek sengketa di tempat kejadian sebagai bagian dari proses pembuktian.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-06-10 00:00:00',
            ],
            [
                'title'       => 'CPNS Resmi Menjadi PNS, Pengadilan Agama Painan Gelar Pelantikan dan Pengambilan Sumpah',
                'category_name' => 'Informasi',
                'excerpt'    => 'Pengadilan Agama Painan melantik ASN dalam rangka perubahan status dari Calon Pegawai Negeri Sipil (CPNS) menjadi Pegawai Negeri Sipil (PNS).',
                'content'    => "Painan | Kamis, 3 Juni 2026\n\nPengadilan Agama Painan menggelar upacara pelantikan dan pengambilan sumpah CPNS yang resmi menjadi PNS. Ketua Pengadilan Agama Painan, Milda Sukmawati, S.H.I., memberikan arahan kepada seluruh pejabat baru.\n\nKetua pengadilan mengucapkan selamat dan menekankan pentingnya integritas serta profesionalisme dalam pelayanan publik.\n\nPara PNS baru dijanjikan untuk terus meningkatkan kompetensi, dedikasi, dan memberikan pelayanan terbaik bagi masyarakat pencari keadilan di Pengadilan Agama Painan.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-06-03 00:00:00',
            ],
            [
                'title'       => 'Upacara Peringatan Hari Lahir Pancasila Tahun 2026 di Pengadilan Agama Painan',
                'category_name' => 'Informasi',
                'excerpt'    => 'PA Painan menggelar upacara peringatan Hari Lahir Pancasila 2026 sebagai wujud penanaman nilai-nilai Pancasila dalam kehidupan sehari-hari.',
                'content'    => "Painan | Rabu, 1 Juni 2026\n\nPengadilan Agama Painan menggelar upacara peringatan Hari Lahir Pancasila Tahun 2026 yang digelar di halaman kantor PA Painan.\n\nUpacara yang dihadiri seluruh Hakim, ASN, CPNS, dan PPNPA ini bertujuan untuk mengingatkan kembali nilai-nilai Pancasila sebagai dasar negara yang harus senantiasa diamalkan dalam kehidupan sehari-hari, termasuk dalam pelaksanaan tugas sebagai aparatur pengadilan.\n\nPancasila menjadi pedoman dalam menjalankan peran pengadilan sebagai penegak hukum yang berkeadilan dan berintegritas.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-06-01 00:00:00',
            ],
            [
                'title'       => 'Coffee Morning Pengadilan Agama Painan',
                'category_name' => 'Informasi',
                'excerpt'    => 'Coffee Morning pagi ini dipimpin oleh Wakil Ketua M. Jimmy Kurniawan untuk meningkatkan keakraban dan motivasi kerja aparatur PA Painan.',
                'content'    => "Painan | Jumat, 29 Juni 2026\n\nSetelah apel rutin pagi, Pengadilan Agama Painan mengadakan acara Coffee Morning yang dipimpin oleh Wakil Ketua Pengadilan Agama Painan, Bapak M. Jimmy Kurniawan, S.H.I.\n\nAcara ini bertujuan untuk meningkatkan silaturahmi dan keakraban antar sesama aparatur pengadilan, serta sebagai ajang evaluasi informal mengenai kinerja dan motivasi kerja.\n\nSeluruh Hakim, ASN, CPNS, dan PPNPA turut berpartisipasi dalam Coffee Morning yang berlangsung santai dan penuh semangat ini.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-05-29 00:00:00',
            ],
            [
                'title'       => 'Apel Rutin Pagi Pengadilan Agama Painan',
                'category_name' => 'Informasi',
                'excerpt'    => 'Apel rutin pagi yang dipimpin Ketua PA Painan menjadi momen membangun semangat kerja dan koordinasi harian seluruh aparatur pengadilan.',
                'content'    => "Painan | Senin, 29 Juni 2026\n\nPengadilan Agama Painan melaksanakan Apel Rutin Pagi yang dipimpin langsung oleh Ketua PA Painan, Ibu Milda Sukmawati, S.H.I.\n\nDalam arahannya, Ketua PA Painan menyampaikan agar seluruh aparatur tetap semangat dan memberikan kinerja terbaik sebagai bentuk pengabdian dan keikhlasan.\n\nIbu Ketua juga menghimbau untuk terus menjaga protokol kesehatan, melayani pihak berperara dengan prima, dan memulai kegiatan bekerja dengan bismillahirrahmanirrahim.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-05-25 00:00:00',
            ],
            [
                'title'       => 'Apel Rutin Jumat Sore PA Painan',
                'category_name' => 'Informasi',
                'excerpt'    => 'Apel Jumat sore yang dipimpin Wakil Ketua M. Jimmy Kurniawan menjadi sarana evaluasi mingguan dan penyemangat akhir pekan bagi aparatur PA Painan.',
                'content'    => "Painan | Jumat, 5 Juli 2024\n\nPengadilan Agama (PA) Painan melaksanakan Apel Rutin Jum'at Sore di halaman Lobby kantor. Pembina Apel Sore adalah Wakil Ketua Pengadilan Agama Painan, Bapak M. Jimmy Kurniawan, S.H.I.\n\nDalam Apel Sore ini, Pembina Apel menyampaikan terima kasih kepada semua pegawai PA Painan atas kontribusi dan kerja sama.\n\nPembina Apel juga mengucapkan selamat berlibur, selamat berkumpul bersama keluarga, selamat berakhir pekan, tetap jaga kesehatan, dan jangan lupa bahagia.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-05-12 00:00:00',
            ],
            [
                'title'       => 'PA Painan Ikuti Sosialisasi PERMA Nomor 4 Tahun 2025 tentang Perlindungan Konsumen',
                'category_name' => 'Regulasi',
                'excerpt'    => 'PA Painan ikut serta dalam sosialisasi PERMA Nomor 4 Tahun 2025 tentang tata cara mengadili gugatan yang diajukan oleh Otoritas Jasa Keuangan.',
                'content'    => "Painan | Kamis, 09 April 2026\n\nPengadilan Agama (PA) Painan yang dihadiri Ketua PA Painan, Ibu Zakiyah Ulya, S.H.I, didampingi Hakim dan Panitera Bapak Jacki Efrizon, S.H beserta jajaran Staff Kepaniteraan menghadiri dan mengikuti Sosialisasi Peraturan Mahkamah Agung RI Nomor 4 Tahun 2025 tentang Tata Cara Mengadili Gugatan yang Diajukan oleh Otoritas Jasa Keuangan Sebagai Upaya Perlindungan Konsumen secara daring.\n\nKegiatan berlangsung di ruang Media Center PA Painan dengan narasumber Y.M. Dr. H. Yasardin, S.H., M.Hum.\n\nSosialisasi ini bertujuan memberikan pemahaman komprehensif kepada hakim, panitera, dan staf mengenai mekanisme dan prosedur penanganan gugatan dari OJK, yang mencerminkan semakin meningkatnya kebutuhan perlindungan hukum di sektor jasa keuangan.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-04-09 00:00:00',
            ],
            [
                'title'       => 'Briefing Petugas PTSP Pengadilan Agama Painan',
                'category_name' => 'Informasi',
                'excerpt'    => 'Hakim PA Painan memimpin briefing petugas PTSP untuk menerapkan budaya 5S/5R dan memberikan pelayanan prima kepada masyarakat.',
                'content'    => "Painan | Kamis, 09 April 2026\n\nHakim Paramitha Try Andini, S.H., M.H. memimpin briefing petugas PTSP (Pe layanan Terpadu Satu Pintu) Pengadilan Agama Painan di ruangan PTSP.\n\nDalam arahannya, Hakim Paramitha mengingatkan para petugas untuk konsisten menerapkan budaya layanan 5S — Senyum, Salam, Sapa, Sopan, dan Santun — serta budaya kerja 5R — Rapi, Resik, Rawat, Rajin, Ringkas.\n\nHakim Paramitha juga menghimbau petugas PTSP untuk selalu menjaga iman dan taqwa, serta memberikan pelayanan prima kepada seluruh lapisan masyarakat yang datang ke PA Painan.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-04-09 00:00:00',
            ],
            [
                'title'       => 'Rapat Kerja Pengadilan Agama Painan Tahun 2026',
                'category_name' => 'Informasi',
                'excerpt'    => 'PA Painan mengadakan Rapat Kerja tahunan untuk menyusun program kerja 2026 yang berfokus pada peradilan profesional, transparan, dan berintegritas.',
                'content'    => "Painan | Selasa, 07 April 2026\n\nSeluruh Aparatur Pengadilan Agama Painan melaksanakan Rapat Kerja (Raker) Tahun 2026 yang dipimpin langsung oleh Ketua PA Painan Ibu Zakiyah Ulya, S.H.I., M.H.\n\nDalam arahannya, pimpinan menyampaikan pentingnya komitmen bersama dalam mewujudkan peradilan yang profesional, transparan, dan akuntabel.\n\nRaker ini juga menjadi momentum untuk memperkuat pembangunan Zona Integritas menuju Wilayah Bebas dari Korupsi (WBK) serta peningkatan kualitas pelayanan publik berbasis teknologi informasi.\n\nRapat dilanjutkan dengan sesi masing-masing komis: Komisi A (Manajemen Peradilan), Komisi B (Kesekretariatan), dan Komisi C (Organisasi Internal).",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-04-07 00:00:00',
            ],
            [
                'title'       => 'Upacara Peringatan Hari Kebangkitan Nasional 2026 di PA Painan',
                'category_name' => 'Informasi',
                'excerpt'    => 'PA Painan menggelar upacara peringatan Hari Kebangkitan Nasional 2026 sebagai bentuk penghormatan terhadap semangat persatuan bangsa.',
                'content'    => "Painan | Senin, 25 Mei 2026\n\nPengadilan Agama Painan melaksanakan upacara peringatan Hari Kebangkitan Nasional 2026 di halaman kantor.\n\nUpacara yang dihadiri seluruh Hakim, ASN, CPNS, PPNPA, dan mahasiswa PPL ini bertujuan untuk mengenang dan menginspirasi semangat kebangkitan nasional dalam meningkatkan kualitas pelayanan publik.\n\nSemangat Sumpah Pemuda harus terus kita junjung tinggi dalam mewujudkan pengadilan yang modern dan berintegritas.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-05-25 00:00:00',
            ],
            [
                'title'       => 'Apel Rutin Jumat Sore PA Painan',
                'category_name' => 'Informasi',
                'excerpt'    => 'Apel Jumat sore rutin dilaksanakan di lobby PA Painan sebagai evaluasi dan penyemangat kegiatan selama seminggu.',
                'content'    => "Painan | Jumat, 25 Mei 2026\n\nPengadilan Agama Painan melaksanakan Apel Rutin Jumat Sore di ruang lobby kantor. Apel ini dipimpin langsung oleh Wakil Ketua Pengadilan Agama Painan.\n\nDalam sambutannya, pembina apel menyampaikan apresiasi atas kerja keras seluruh aparatur PA Painan dalam melayani masyarakat selama bulan ini.\n\nAgenda apel Jumat sore ini juga menjadi momen untuk menyampaikan informasi penting kepada seluruh pegawai dan mempersiapkan kegiatan selama seminggu ke depan.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-05-25 00:00:00',
            ],
            [
                'title'       => 'PA Painan Ikuti Kegiatan Halal Bihalal Pengadilan Tinggi Agama Padang Bersama PA Se-Sumatera Barat',
                'category_name' => 'Informasi',
                'excerpt'    => 'PA Painan berpartisipasi dalam kegiatan halal bihalal bersama Pengadilan Tinggi Agama Padang dan PA se-Sumatera Barat.',
                'content'    => "Painan | Kamis, 17 April 2026\n\nPengadilan Agama Painan mewakili diri untuk mengikuti kegiatan Halal Bihalal Pengadilan Tinggi Agama Padang Bersama Pengadilan Agama se-Sumatera Barat.\n\nKegiatan ini merupakan momen silaturahmi antar jajarana peradilan agama se-Sumatera Barat dalam rangka mempererat tali persaudaraan dan memperkokoh sinergi antar institusi.\n\nKegiatan dihadiri oleh seluruh Ketua, Wakil Ketua, Panitera, dan Sekretaris Pengadilan Agama di wilayah Sumatera Barat.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-04-17 00:00:00',
            ],
            [
                'title'       => 'PA Painan Ikuti Rapat Kerja Daerah Pengadilan Agama Se-Sumatera Barat Tahun 2026',
                'category_name' => 'Informasi',
                'excerpt'    => 'Delegasi PA Painan mengikuti Rapat Kerja Daerah (Rakerda) PA se-Sumatera Barat Tahun 2026 yang membahas strategi peningkatan mutu pelayanan.',
                'content'    => "Painan | Rabu, 16 April 2026\n\nPengadilan Agama Painan mengikuti Rapat Kerja Daerah Pengadilan Agama Se-Sumatera Barat Tahun 2026.\n\nRapat yang membahas mengenai program kerja, evaluasi kinerja, dan strategi peningkatan mutu pelayanan di lingkungan Peradilan Agama se-Sumatera Barat ini dihadiri oleh Ketua PA Painan dan perwakilan instansi terkait.\n\nDari hasil rakerda, ditetapkan sejumlah program prioritas yang harus diimplementasikan oleh seluruh Pengadilan Agama se-Sumatera Barat termasuk PA Painan.",
                'author_name' => 'Humas PA Painan',
                'published_at'=> '2026-04-16 00:00:00',
            ],
        ];

        foreach ($news as $item) {
            CmsBlogPost::create($item);
        }

        echo "15 berita berhasil dimasukkan.\n";
    }
}
