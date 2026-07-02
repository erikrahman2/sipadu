<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CmsBlogPost;
use App\Services\ContentService;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    protected ContentService $contentService;

    public function __construct(ContentService $contentService)
    {
        $this->contentService = $contentService;
    }

    /**
     * Homepage — fetch home sections from CMS.
     */
    public function index()
    {
        $homeSections = $this->contentService->getHomeSections();

        return view('welcome-new', [
            'homeSections' => $homeSections,
        ]);
    }

    /**
     * About page — fetch about sections from CMS with fallbacks.
     */
    public function about()
    {
        $cmsData = $this->contentService->getAllAboutSections();

        $about    = $cmsData->get('tentang_sipadu') ?? null;
        $partner  = $cmsData->get('institusi_kerja_sama') ?? null;
        $support  = $cmsData->get('institusi_pendukung') ?? null;
        $visiMisi = $cmsData->get('visi_misi') ?? null;
        $features = $cmsData->get('fitur_keunggulan') ?? null;

        return view('tentang', [
            'hero'      => $hero,
            'visiMisi'  => $visiMisi,
            'process'   => $process,
            'services'  => $services,
            'faq'       => $faq,
        ]);
    }

    /**
     * Blog/news page — fetch published posts from CMS.
     */
    public function blog()
    {
        $posts = $this->contentService->getAllPublishedPosts();

        return view('berita', [
            'posts' => $posts,
        ]);
    }

    /**
     * Single blog post detail.
     */
    public function post(string $slug)
    {
        $post = $this->contentService->getPostBySlug($slug);

        if (!$post) {
            return abort(404);
        }

        return view('berita-detail', [
            'post' => $post,
        ]);
    }
}
