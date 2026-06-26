<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsAboutSection;
use App\Models\CmsBlogPost;
use App\Models\CmsHomeSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CMSKelolaKontenController extends Controller
{
    /* ---------------------------------------------------------------- */
    /*  Index – unified kelola konten dashboard                           */
    /* ---------------------------------------------------------------- */

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'home');
        if (!in_array($tab, ['home', 'blog', 'about'], true)) {
            $tab = 'home';
        }

        $homeSections = CmsHomeSection::orderBy('display_order')->paginate(10);
        $homeCounts = [
            'total'    => CmsHomeSection::count(),
            'active'   => CmsHomeSection::where('is_active', 1)->count(),
            'inactive' => CmsHomeSection::where('is_active', 0)->count(),
        ];

        $blogPosts = CmsBlogPost::orderByDesc('created_at')->paginate(10);
        $blogCounts = [
            'total'     => CmsBlogPost::count(),
            'published' => CmsBlogPost::where('status', 'PUBLISHED')->count(),
            'draft'     => CmsBlogPost::where('status', 'DRAFT')->count(),
            'archived'  => CmsBlogPost::where('status', 'ARCHIVED')->count(),
        ];

        $aboutSections = CmsAboutSection::orderBy('display_order')->paginate(10);
        $aboutCounts = [
            'total'    => CmsAboutSection::count(),
            'active'   => CmsAboutSection::where('is_active', 1)->count(),
            'inactive' => CmsAboutSection::where('is_active', 0)->count(),
        ];

        return view('dashboard.staff.cms.index', compact(
            'tab', 'homeSections', 'homeCounts',
            'blogPosts', 'blogCounts',
            'aboutSections', 'aboutCounts'
        ));
    }

    /**
     * Tampilkan daftar section Homepage.
     */
    public function homeIndex()
    {
        $sections = CmsHomeSection::ordered()->paginate(10);
        return view('dashboard.staff.cms.home.index', compact('sections'));
    }

    /**
     * Tampilkan daftar section Tentang.
     */
    public function aboutIndex()
    {
        $sections = CmsAboutSection::ordered()->paginate(10);
        return view('dashboard.staff.cms.about.index', compact('sections'));
    }

    /**
     * Tampilkan daftar postingan Blog.
     */
    public function blogIndex()
    {
        $posts = CmsBlogPost::with('author')->latest()->paginate(10);
        $blogs = CmsBlogPost::latest()->get();
        $counts = [
            'all'       => CmsBlogPost::count(),
            'published' => CmsBlogPost::where('status', 'PUBLISHED')->count(),
            'draft'     => CmsBlogPost::where('status', 'DRAFT')->count(),
            'archived'  => CmsBlogPost::where('status', 'ARCHIVED')->count(),
        ];

        return view('dashboard.staff.cms.blog.index', compact('posts', 'blogs', 'counts'));
    }

    /* ---------------------------------------------------------------- */
    /*  Homepage CRUD                                                     */
    /* ---------------------------------------------------------------- */

    public function homeCreate()
    {
        return view('dashboard.staff.cms.home.create');
    }

    public function homeStore(Request $request)
    {
        $data = $this->validateHomeSection($request);
        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('cms/home', 'public');
        }

        CmsHomeSection::create($data);

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'home'])
            ->with('success', 'Home section berhasil ditambahkan.');
    }

    public function homeEdit(CmsHomeSection $home)
    {
        return view('dashboard.staff.cms.home.edit', ['section' => $home]);
    }

    public function homeUpdate(Request $request, CmsHomeSection $home)
    {
        $data = $this->validateHomeSection($request, $home->id);
        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        if ($request->hasFile('image_path')) {
            if ($home->image_path) {
                Storage::disk('public')->delete($home->image_path);
            }
            $data['image_path'] = $request->file('image_path')->store('cms/home', 'public');
        }

        $home->update($data);

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'home'])
            ->with('success', 'Home section berhasil diperbarui.');
    }

    public function homeDestroy(CmsHomeSection $home)
    {
        if ($home->image_path) {
            Storage::disk('public')->delete($home->image_path);
        }
        $home->delete();

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'home'])
            ->with('success', 'Home section berhasil dihapus.');
    }

    /* ---------------------------------------------------------------- */
    /*  About CRUD                                                        */
    /* ---------------------------------------------------------------- */

    public function aboutCreate()
    {
        return view('dashboard.staff.cms.about.create');
    }

    public function aboutStore(Request $request)
    {
        $data = $this->validateAboutSection($request);
        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('cms/about', 'public');
        }

        CmsAboutSection::create($data);

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'about'])
            ->with('success', 'About section berhasil ditambahkan.');
    }

    public function aboutEdit(CmsAboutSection $about)
    {
        return view('dashboard.staff.cms.about.edit', ['section' => $about]);
    }

    public function aboutUpdate(Request $request, CmsAboutSection $about)
    {
        $data = $this->validateAboutSection($request, $about->id);
        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        if ($request->hasFile('image_path')) {
            if ($about->image_path) {
                Storage::disk('public')->delete($about->image_path);
            }
            $data['image_path'] = $request->file('image_path')->store('cms/about', 'public');
        }

        $about->update($data);

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'about'])
            ->with('success', 'About section berhasil diperbarui.');
    }

    public function aboutDestroy(CmsAboutSection $about)
    {
        if ($about->image_path) {
            Storage::disk('public')->delete($about->image_path);
        }
        $about->delete();

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'about'])
            ->with('success', 'About section berhasil dihapus.');
    }

    /* ---------------------------------------------------------------- */
    /*  Blog CRUD                                                         */
    /* ---------------------------------------------------------------- */

    public function blogCreate()
    {
        return view('dashboard.staff.cms.blog.create');
    }

    public function blogStore(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:cms_blog_posts,slug',
            'content'     => 'required|string',
            'excerpt'     => 'nullable|string|max:500',
            'author_name' => 'nullable|string|max:255',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'status'      => 'required|in:DRAFT,PUBLISHED,ARCHIVED',
        ]);

        $data['slug'] = $data['slug'] ?? \Str::slug($data['title']);
        $data['updated_by'] = optional($request->user())->id;

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('cms/blog', 'public');
        }

        CmsBlogPost::create($data);

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'blog'])
            ->with('success', 'Artikel berhasil ditambahkan.');
    }

    public function blogEdit(CmsBlogPost $post)
    {
        return view('dashboard.staff.cms.blog.edit', ['post' => $post]);
    }

    public function blogUpdate(Request $request, CmsBlogPost $post)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:cms_blog_posts,slug,' . $post->id,
            'content'     => 'required|string',
            'excerpt'     => 'nullable|string|max:500',
            'author_name' => 'nullable|string|max:255',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'status'      => 'required|in:DRAFT,PUBLISHED,ARCHIVED',
        ]);

        $data['slug'] = $data['slug'] ?? \Str::slug($data['title']);
        $data['updated_by'] = optional($request->user())->id;

        if ($request->hasFile('cover_image')) {
            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('cms/blog', 'public');
        }

        $post->update($data);

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'blog'])
            ->with('success', 'Artikel berhasil diperbarui.');
    }

    public function blogDestroy(CmsBlogPost $post)
    {
        if ($post->cover_image) {
            Storage::disk('public')->delete($post->cover_image);
        }
        $post->delete();

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'blog'])
            ->with('success', 'Artikel berhasil dihapus.');
    }

    /* ---------------------------------------------------------------- */
    /*  Validation helpers                                                */
    /* ---------------------------------------------------------------- */

    protected function validateHomeSection(Request $request, ?int $excludeId = null): array
    {
        $rules = [
            'section_key'   => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'title'         => 'required|string|max:255',
            'subtitle'      => 'nullable|string|max:300',
            'content'       => 'nullable|string',
            'image_path'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cta_label'     => 'nullable|string|max:100',
            'cta_url'       => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
            'is_active'     => 'sometimes|boolean',
        ];

        if ($excludeId) {
            $rules['section_key'] .= ',cms_home_sections,section_key,' . $excludeId;
        } else {
            $rules['section_key'] .= '|unique:cms_home_sections,section_key';
        }

        return $request->validate($rules);
    }

    protected function validateAboutSection(Request $request, ?int $excludeId = null): array
    {
        $rules = [
            'section_key'   => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'title'         => 'required|string|max:255',
            'subtitle'      => 'nullable|string|max:300',
            'content'       => 'nullable|string',
            'image_path'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'display_order' => 'nullable|integer|min:0',
            'is_active'     => 'sometimes|boolean',
        ];

        if ($excludeId) {
            $rules['section_key'] .= ',cms_about_sections,section_key,' . $excludeId;
        } else {
            $rules['section_key'] .= '|unique:cms_about_sections,section_key';
        }

        return $request->validate($rules);
    }
}
