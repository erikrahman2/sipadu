<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsAboutSection;
use App\Models\CmsBlogPost;
use App\Models\CmsHomeSection;
use App\Models\Layan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CMSKelolaKontenController extends Controller
{
    /* ---------------------------------------------------------------- */
    /*  Index – unified kelola konten dashboard                           */
    /* ---------------------------------------------------------------- */

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'beranda');
        if (!in_array($tab, ['beranda', 'layanan', 'tentang', 'berita'], true)) {
            $tab = 'beranda';
        }

        $homeSections = CmsHomeSection::orderBy('display_order')->get();
        $homeCounts = [
            'total'    => CmsHomeSection::count(),
            'active'   => CmsHomeSection::where('is_active', 1)->count(),
            'inactive' => CmsHomeSection::where('is_active', 0)->count(),
        ];

        $aboutSections = CmsAboutSection::orderBy('display_order')->get();
        $aboutCounts = [
            'total'    => CmsAboutSection::count(),
            'active'   => CmsAboutSection::where('is_active', 1)->count(),
            'inactive' => CmsAboutSection::where('is_active', 0)->count(),
        ];

        $layans = Layan::orderBy('urutan')->get();
        $layanCounts = [
            'total'    => Layan::count(),
            'aktif'    => Layan::where('aktif', true)->count(),
            'nonaktif' => Layan::where('aktif', false)->count(),
        ];

        $blogPosts = CmsBlogPost::orderByDesc('id')->get();
        $blogCounts = [
            'total'     => CmsBlogPost::count(),
            'published' => CmsBlogPost::where('status', 'PUBLISHED')->count(),
            'draft'     => CmsBlogPost::where('status', 'DRAFT')->count(),
            'archived'  => CmsBlogPost::where('status', 'ARCHIVED')->count(),
        ];

        return view('dashboard.admin.cms.kelola-konten.index', compact(
            'tab', 'homeSections', 'homeCounts',
            'aboutSections', 'aboutCounts',
            'layans', 'layanCounts',
            'blogPosts', 'blogCounts'
        ));
    }

    /* ---------------------------------------------------------------- */
    /*  Homepage CRUD                                                     */
    /* ---------------------------------------------------------------- */

    public function homeCreate()
    {
        return view('dashboard.admin.cms.kelola-konten.create-home');
    }

    public function homeStore(Request $request)
    {
        $data = $this->validateHomeSection($request);
        $data['content_type'] = $request->input('content_type');
        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('cms/home', 'public');
        }

        CmsHomeSection::create($data);

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'beranda'])
            ->with('success', 'Home section berhasil ditambahkan.');
    }

    public function homeEdit(CmsHomeSection $home)
    {
        return view('dashboard.admin.cms.kelola-konten.edit-home', ['section' => $home]);
    }

    public function homeUpdate(Request $request, CmsHomeSection $home)
    {
        $data = $this->validateHomeSection($request, $home->id);
        $data['content_type'] = $request->input('content_type');
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

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'beranda'])
            ->with('success', 'Home section berhasil diperbarui.');
    }

    public function homeDestroy(CmsHomeSection $home)
    {
        if ($home->image_path) {
            Storage::disk('public')->delete($home->image_path);
        }
        $home->delete();

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'beranda'])
            ->with('success', 'Home section berhasil dihapus.');
    }

    /* ---------------------------------------------------------------- */
    /*  About CRUD                                                        */
    /* ---------------------------------------------------------------- */

    public function aboutCreate()
    {
        return view('dashboard.admin.cms.kelola-konten.create-about');
    }

    public function aboutStore(Request $request)
    {
        $data = $this->validateAboutSection($request);
        $data['content_type'] = $request->input('content_type');
        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('cms/about', 'public');
        }

        CmsAboutSection::create($data);

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'tentang'])
            ->with('success', 'About section berhasil ditambahkan.');
    }

    public function aboutEdit(CmsAboutSection $about)
    {
        return view('dashboard.admin.cms.kelola-konten.edit-about', ['section' => $about]);
    }

    public function aboutUpdate(Request $request, CmsAboutSection $about)
    {
        $data = $this->validateAboutSection($request, $about->id);
        $data['content_type'] = $request->input('content_type');
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

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'tentang'])
            ->with('success', 'About section berhasil diperbarui.');
    }

    public function aboutDestroy(CmsAboutSection $about)
    {
        if ($about->image_path) {
            Storage::disk('public')->delete($about->image_path);
        }
        $about->delete();

        return redirect()->route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'tentang'])
            ->with('success', 'About section berhasil dihapus.');
    }

    /* ---------------------------------------------------------------- */
    /*  Blog CRUD                                                         */
    /* ---------------------------------------------------------------- */

    public function blogCreate()
    {
        return view('dashboard.admin.cms.kelola-konten.create-blog');
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
        return view('dashboard.admin.cms.kelola-konten.edit-blog', ['post' => $post]);
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
            'content_type'    => 'required|string|max:100'.($excludeId ? ':cms_home_sections,content_type,'.$excludeId : '|unique:cms_home_sections,content_type'),
            'title'           => 'required|string|max:255',
            'subtitle'        => 'nullable|string|max:300',
            'content'         => 'nullable|string',
            'image_path'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cta_label'       => 'nullable|string|max:100',
            'cta_url'         => 'nullable|string|max:255',
            'secondary_cta_url' => 'nullable|string|max:255',
            'display_order'   => 'nullable|integer|min:0',
            'is_active'       => 'sometimes|boolean',
        ];

        return $request->validate($rules);
    }

    protected function validateAboutSection(Request $request, ?int $excludeId = null): array
    {
        $rules = [
            'content_type'    => 'required|string|max:100'.($excludeId ? ':cms_about_sections,content_type,'.$excludeId : '|unique:cms_about_sections,content_type'),
            'title'           => 'required|string|max:255',
            'subtitle'        => 'nullable|string|max:300',
            'content'         => 'nullable|string',
            'image_path'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'display_order'   => 'nullable|integer|min:0',
            'is_active'       => 'sometimes|boolean',
        ];

        return $request->validate($rules);
    }
}
