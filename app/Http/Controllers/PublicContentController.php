<?php

namespace App\Http\Controllers;

use App\Models\CmsBlogPost;
use App\Models\PublicCms;
use Illuminate\Http\Request;

class PublicContentController extends Controller
{
    public function index()
    {
        $berandaHero    = PublicCms::getByKey('beranda-hero');
        $berandaIntro   = PublicCms::getByKey('beranda-intro');
        $berandaFeatures = PublicCms::where('section_key', 'LIKE', 'beranda-feature%')
            ->active()->ordered()->get();
        $berandaCtas     = PublicCms::where('section_key', 'LIKE', 'beranda-cta%')
            ->active()->ordered()->get();
        $berandaInstitut = PublicCms::where('section_key', 'LIKE', 'beranda-institusi%')
            ->active()->ordered()->get();

        $hero = $this->heroFallback($berandaHero);
        $intro = $this->introFallback($berandaIntro);
        $features = $this->featuresFallback($berandaFeatures);
        $ctas = $this->ctasFallback($berandaCtas);
        $institu = $this->instituFallback($berandaInstitut);

        return view('welcome-new', compact(
            'hero', 'intro', 'features', 'ctas', 'institu'
        ));
    }

    public function tentang()
    {
        $aboutContent   = PublicCms::getByKey('tentang-konten');
        $visi           = PublicCms::getByKey('tentang-visi');
        $misi           = PublicCms::getByKey('tentang-misi');
        $fiturItems     = PublicCms::where('section_key', 'LIKE', 'tentang-fitur%')
            ->active()->ordered()->get();
        $institu        = PublicCms::where('section_key', 'LIKE', 'tentang-institusi%')
            ->active()->ordered()->get();

        $about = $this->aboutContentFallback($aboutContent);
        $visiData = $this->visiFallback($visi);
        $misiData = $this->misiFallback($misi);
        $features = $this->tentangFeaturesFallback($fiturItems);
        $instituData = $this->tentangInstituFallback($institu);

        return view('tentang', compact(
            'about', 'visiData', 'misiData', 'features', 'instituData'
        ));
    }

    public function berita()
    {
        $featured = CmsBlogPost::published()
            ->orderByDesc('published_at')
            ->first();

        $posts = CmsBlogPost::published()
            ->whereNotNull('id')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        $blogHeading = PublicCms::getByKey('berita-heading');
        $newsletter = PublicCms::getByKey('berita-newsletter');

        return view('berita', compact(
            'featured', 'posts', 'blogHeading', 'newsletter'
        ));
    }

    // --- Fallback helpers ---

    private function heroFallback($cms)
    {
        if ($cms && $cms->title) {
            return (object)[
                'title' => $cms->title,
                'subtitle' => $cms->subtitle ?? '',
                'content' => $cms->content ?? '',
                'cta_label' => $cms->cta_label ?? 'Mulai Sekarang',
                'cta_url' => $cms->cta_url ?? route('public.submit.create'),
            ];
        }

        return (object)[
            'title' => 'Selamat Datang di SiPadu',
            'subtitle' => 'Sistem Pembaruan Dokumen Pasca Perceraian',
            'content' => 'Terintegrasi antara Pengadilan Agama dan Disdukcapil.',
            'cta_label' => 'Ajukan Pembaruan Dokumen',
            'cta_url' => route('public.submit.create'),
        ];
    }

    private function introFallback($cms)
    {
        if ($cms && $cms->content) {
            return (object)['content' => $cms->content];
        }
        return (object)['content' => ''];
    }

    private function featuresFallback($cmsItems)
    {
        if ($cmsItems && $cmsItems->isNotEmpty()) {
            return $cmsItems->map(function ($item) {
                return (object)[
                    'title' => $item->title,
                    'content' => $item->content,
                    'icon' => $item->subtitle ?? 'fas fa-check',
                ];
            });
        }
        return collect([]);
    }

    private function ctasFallback($cmsItems)
    {
        if ($cmsItems && $cmsItems->isNotEmpty()) {
            return $cmsItems->map(function ($item) {
                return (object)[
                    'title' => $item->title,
                    'subtitle' => $item->subtitle ?? '',
                    'content' => $item->content,
                    'cta_label' => $item->cta_label,
                    'cta_url' => $item->cta_url,
                ];
            });
        }
        return collect([]);
    }

    private function instituFallback($cmsItems)
    {
        if ($cmsItems && $cmsItems->isNotEmpty()) {
            return $cmsItems->map(function ($item) {
                return (object)[
                    'title' => $item->title,
                    'content' => $item->content,
                ];
            });
        }
        return collect([]);
    }

    private function aboutContentFallback($cms)
    {
        if ($cms && $cms->content) {
            return (object)[
                'title' => $cms->title ?? 'Apa Itu SiPadu?',
                'content' => $cms->content,
            ];
        }
        return (object)[
            'title' => 'Apa Itu SiPadu?',
            'content' => 'Sistem Pembaruan Dokumen Pasca Perceraian (SiPadu) adalah platform terintegrasi...',
        ];
    }

    private function visiFallback($cms)
    {
        if ($cms && $cms->content) {
            return (object)[
                'title' => $cms->title ?? 'Visi',
                'content' => $cms->content,
            ];
        }
        return (object)['title' => 'Visi', 'content' => ''];
    }

    private function misiFallback($cms)
    {
        if ($cms && $cms->content) {
            return (object)[
                'title' => $cms->title ?? 'Misi',
                'content' => $cms->content,
            ];
        }
        return (object)['title' => 'Misi', 'content' => ''];
    }

    private function tentangFeaturesFallback($cmsItems)
    {
        if ($cmsItems && $cmsItems->isNotEmpty()) {
            return $cmsItems->map(function ($item) {
                return (object)[
                    'title' => $item->title,
                    'content' => $item->content,
                ];
            });
        }
        return collect([
            (object)['title' => 'Pengajuan Tanpa Akun', 'content' => ''],
            (object)['title' => 'OCR Otomatis', 'content' => ''],
            (object)['title' => 'Verifikasi berlapis', 'content' => ''],
            (object)['title' => 'Pelacakan Real-Time', 'content' => ''],
        ]);
    }

    private function tentangInstituFallback($cmsItems)
    {
        if ($cmsItems && $cmsItems->isNotEmpty()) {
            return $cmsItems->map(function ($item) {
                return (object)[
                    'title' => $item->title,
                    'content' => $item->content,
                ];
            });
        }
        return collect([
            (object)['title' => 'Pengadilan Agama', 'content' => ''],
            (object)['title' => 'Disdukcapil', 'content' => ''],
        ]);
    }
}
