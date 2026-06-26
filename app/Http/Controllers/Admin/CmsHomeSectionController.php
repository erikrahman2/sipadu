<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsHomeSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CmsHomeSectionController extends Controller
{
    public function index()
    {
        $sections = CmsHomeSection::ordered()->get();
        return view('dashboard.admin.cms.home.index', compact('sections'));
    }

    public function create()
    {
        return view('dashboard.admin.cms.home.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'section_key'   => 'required|string|max:100|unique:cms_home_sections,section_key|regex:/^[a-z0-9_]+$/',
            'title'         => 'required|string|max:255',
            'subtitle'      => 'nullable|string|max:300',
            'content'       => 'nullable|string',
            'image_path'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cta_label'     => 'nullable|string|max:100',
            'cta_url'       => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
            'is_active'     => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('cms/home', 'public');
        }

        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        CmsHomeSection::create($data);

        return redirect()->route('dashboard.admin.cms.home.index')
            ->with('success', 'Home section berhasil ditambahkan.');
    }

    public function edit(CmsHomeSection $home)
    {
        return view('dashboard.admin.cms.home.edit', ['section' => $home]);
    }

    public function update(Request $request, CmsHomeSection $home)
    {
        $data = $request->validate([
            'section_key'   => 'required|string|max:100|unique:cms_home_sections,section_key,' . $home->id . '|regex:/^[a-z0-9_]+$/',
            'title'         => 'required|string|max:255',
            'subtitle'      => 'nullable|string|max:300',
            'content'       => 'nullable|string',
            'image_path'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cta_label'     => 'nullable|string|max:100',
            'cta_url'       => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
            'is_active'     => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image_path')) {
            if ($home->image_path) {
                Storage::disk('public')->delete($home->image_path);
            }
            $data['image_path'] = $request->file('image_path')->store('cms/home', 'public');
        }

        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = optional($request->user())->id;
        $data['display_order'] = $data['display_order'] ?? 0;

        $home->update($data);

        return redirect()->route('dashboard.admin.cms.home.index')
            ->with('success', 'Home section berhasil diperbarui.');
    }

    public function destroy(CmsHomeSection $home)
    {
        if ($home->image_path) {
            Storage::disk('public')->delete($home->image_path);
        }
        $home->delete();

        return redirect()->route('dashboard.admin.cms.home.index')
            ->with('success', 'Home section berhasil dihapus.');
    }
}