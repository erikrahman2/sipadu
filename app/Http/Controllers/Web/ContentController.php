<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CmsBlogPost;
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
        return view('berita', compact('posts', 'allPosts'));
    }

    public function blogPost(string $slug)
    {
        $post = $this->content->getPostBySlug($slug);
        if (!$post) {
            return abort(404);
        }
        return view('berita-single', compact('post'));
    }
}
