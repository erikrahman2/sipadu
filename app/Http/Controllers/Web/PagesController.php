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
            ->keyBy('section_key');

        return view('welcome-new', [
            'homeSections' => $sections,
        ]);
    }

    public function tentang()
    {
        $sections = CmsAboutSection::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->get()
            ->keyBy('section_key');

        return view('tentang', [
            'aboutSections' => $sections,
        ]);
    }

    public function berita()
    {
        $header = CmsHomeSection::where('section_key', 'blog_header')
            ->first();

        $posts = CmsBlogPost::published()
            ->ordered()
            ->with('author')
            ->paginate(12);

        return view('berita', [
            'blogHeader' => $header,
            'posts'      => $posts,
        ]);
    }
}
