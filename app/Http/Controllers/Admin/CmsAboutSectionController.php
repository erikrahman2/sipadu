<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsAboutSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CmsAboutSectionController extends Controller
{
    public function index()
    {
        $sections = CmsAboutSection::ordered()->get();
        return view('dashboard.admin.cms.about.index', compact('sections'));
    }

    public function create()
    {
        return view('dashboard.admin.cms.about.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'content_type'   => 'required|string|max:100|unique:cms_about_sections,content_type|regex:/^[a-z0-9_]+$/',
            'title'         => 'required|string|max:255',
            'subtitle'      => 'nullable|string|max:300',
            'content'       => 'required|string',
            'image_path'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'display_order' => 'nullable|integer|min:0',
            'is_active'     => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('cms/about', 'public');
        }

        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        CmsAboutSection::create($data);

        return redirect()->route('dashboard.admin.cms.about.index')
            ->with('success', 'About section berhasil ditambahkan.');
    }

    public function edit(CmsAboutSection $about)
    {
        return view('dashboard.admin.cms.about.edit', ['section' => $about]);
    }

    public function update(Request $request, CmsAboutSection $about)
    {
        $data = $request->validate([
            'content_type'   => 'required|string|max:100|unique:cms_about_sections,content_type,' . $about->id . '|regex:/^[a-z0-9_]+$/',
            'title'         => 'required|string|max:255',
            'subtitle'      => 'nullable|string|max:300',
            'content'       => 'required|string',
            'image_path'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'display_order' => 'nullable|integer|min:0',
            'is_active'     => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image_path')) {
            if ($about->image_path) {
                Storage::disk('public')->delete($about->image_path);
            }
            $data['image_path'] = $request->file('image_path')->store('cms/about', 'public');
        }

        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        $about->update($data);

        return redirect()->route('dashboard.admin.cms.about.index')
            ->with('success', 'About section berhasil diperbarui.');
    }

    public function destroy(CmsAboutSection $about)
    {
        if ($about->image_path) {
            Storage::disk('public')->delete($about->image_path);
        }
        $about->delete();

        return redirect()->route('dashboard.admin.cms.about.index')
            ->with('success', 'About section berhasil dihapus.');
    }
}
