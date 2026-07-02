<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CmsBlogPost;
use App\Models\Layan;
use App\Services\ContentService;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function __construct(protected ContentService $content) {}

    public function homepage()
    {
        $homeSections = $this->content->getHomeSections();
        $blogPosts = $this->content->getPublishedPosts(3);
        return view('welcome-new', compact('homeSections', 'blogPosts'));
    }

    public function aboutPage()
    {
        $sections = $this->content->getAllAboutSections();
        return view('tentang', compact('sections'));
    }

    public function blogPage(Request $request)
    {
        $posts = CmsBlogPost::published()
            ->with(['author'])
            ->orderByDesc('published_at')
            ->paginate((int) $request->input('per_page', 6));

        $allPosts = $this->content->getAllPublishedPosts();

        // CMS-managed hero banner for the Berita page (look for active blog hero section by content type)
        $heroBanner = CmsHomeSection::where('content_type', 'blog_header')
            ->where('is_active', true)
            ->first();

        return view('berita', compact('posts', 'allPosts', 'heroBanner'));
    }

    public function blogPost(string $slug)
    {
        $post = $this->content->getPostBySlug($slug);
        if (!$post) {
            return abort(404);
        }
        return view('berita-single', compact('post'));
    }

    public function layananPage()
    {
        $layans = Layan::where('aktif', true)
            ->orderByRaw("
                FIELD(kategori, 'pencatatan_sipil', 'pembaruan_dokumen', 'perkawinan', 'identitas_digital', 'lainnya')
            ")
            ->orderBy('urutan')
            ->get();

        // Group by kategori
        $groups = $layans->groupBy('kategori')->map(function ($items) {
            return $items->map(fn($item) => [
                'nama' => $item->nama,
                'deskripsi' => $item->deskripsi,
                'icon' => $item->icon,
                'url' => route('public.submit.create'),
            ])->toArray();
        })->toArray();

        // Default categories mapping
        $categories = [
            'pencatatan_sipil' => [
                'title' => 'Pencatatan Sipil',
                'subtitle' => 'Pengurusan dokumen identitas dasar kependudukan',
                'icon' => 'fas fa-clipboard-list',
            ],
            'pembaruan_dokumen' => [
                'title' => 'Pembaruan Dokumen',
                'subtitle' => 'Perubahan data dokumen akibat perubahan status pribadi',
                'icon' => 'fas fa-arrows-rotate',
            ],
            'perkawinan' => [
                'title' => 'Perceraian',
                'subtitle' => 'Layanan terkait proses perceraian dan transmisi putusan',
                'icon' => 'fas fa-gavel',
            ],
            'identitas_digital' => [
                'title' => 'Identitas Digital',
                'subtitle' => 'Layanan identitas digital terintegrasi',
                'icon' => 'fas fa-mobile-screen-button',
            ],
            'lainnya' => [
                'title' => 'Lainnya',
                'subtitle' => 'Layanan lainnya',
                'icon' => 'fas fa-question',
            ],
        ];

        return view('services', compact('groups', 'categories'));
    }
}
