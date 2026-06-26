<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsBlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsBlogPostController extends Controller
{
    public function index(Request $request)
    {
        $query = CmsBlogPost::query();

        if ($request->filled('q')) {
            $needle = $request->q;
            $query->where(function ($q) use ($needle) {
                $q->where('title', 'like', "%{$needle}%")
                  ->orWhere('excerpt', 'like', "%{$needle}%")
                  ->orWhere('content', 'like', "%{$needle}%")
                  ->orWhere('author_name', 'like', "%{$needle}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $posts = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $counts = [
            'all'       => CmsBlogPost::count(),
            'draft'     => CmsBlogPost::where('status', 'DRAFT')->count(),
            'published' => CmsBlogPost::where('status', 'PUBLISHED')->count(),
            'archived'  => CmsBlogPost::where('status', 'ARCHIVED')->count(),
        ];

        return view('dashboard.admin.cms.blog.index', compact('posts', 'counts'));
    }

    public function create()
    {
        return view('dashboard.admin.cms.blog.create', ['post' => new CmsBlogPost()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'nullable|string|max:255|alpha_dash',
            'excerpt'       => 'required|string|max:500',
            'content'       => 'required|string',
            'author_name'   => 'nullable|string|max:100',
            'cover_image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'        => 'required|in:DRAFT,PUBLISHED,ARCHIVED',
            'published_at'  => 'nullable|date',
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('cms/blog', 'public');
        }

        if (!empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        if (($validated['status'] ?? null) === 'PUBLISHED' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $userId = optional($request->user())->id;
        $validated['author_id']  = $userId;
        $validated['updated_by'] = $userId;
        if (empty($validated['author_name']) && $request->user()) {
            $validated['author_name'] = $request->user()->name;
        }

        CmsBlogPost::create($validated);

        return redirect()->route('dashboard.admin.cms.blog.index')
            ->with('success', 'Berita berhasil ditambahkan.');
    }

    public function edit(CmsBlogPost $post)
    {
        return view('dashboard.admin.cms.blog.edit', ['post' => $post]);
    }

    public function update(Request $request, CmsBlogPost $post)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'nullable|string|max:255|alpha_dash',
            'excerpt'       => 'required|string|max:500',
            'content'       => 'required|string',
            'author_name'   => 'nullable|string|max:100',
            'cover_image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'        => 'required|in:DRAFT,PUBLISHED,ARCHIVED',
            'published_at'  => 'nullable|date',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('cms/blog', 'public');
        }

        if (!empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        if (($validated['status'] ?? null) === 'PUBLISHED' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $validated['updated_by'] = optional($request->user())->id;
        $post->update($validated);

        return redirect()->route('dashboard.admin.cms.blog.index')
            ->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(CmsBlogPost $post)
    {
        if ($post->cover_image) {
            Storage::disk('public')->delete($post->cover_image);
        }
        $post->delete();

        return redirect()->route('dashboard.admin.cms.blog.index')
            ->with('success', 'Berita berhasil dihapus.');
    }
}
