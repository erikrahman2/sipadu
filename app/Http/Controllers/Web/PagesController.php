<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CmsHomeSection;
use App\Models\CmsAboutSection;
use App\Models\CmsBlogPost;

class PagesController extends Controller
{
    public function home()
    {
        $sections = CmsHomeSection::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->get()
            ->keyBy('content_type');

        return view('welcome-new', [
            'homeSections' => $sections,
        ]);
    }

    public function tentang()
    {
        $sections = CmsAboutSection::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->get()
            ->keyBy('content_type');

        return view('tentang', [
            'aboutSections' => $sections,
        ]);
    }

    public function berita()
    {
        $heroBanner = CmsHomeSection::where('content_type', 'hero_banner')
            ->first();

        $header = CmsHomeSection::where('content_type', 'blog_header')
            ->first();

        $posts = CmsBlogPost::published()
            ->ordered()
            ->with('author')
            ->paginate(12);

        return view('berita', [
            'heroBanner' => $heroBanner,
            'blogHeader' => $header,
            'posts'      => $posts,
        ]);
    }
}
