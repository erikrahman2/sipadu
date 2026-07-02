<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Layan;
use Illuminate\Http\Request;

class CmsLayanController extends Controller
{
    /* ---------------------------------------------------------------- */
    /*  Index                                                           */
    /* ---------------------------------------------------------------- */

    public function index()
    {
        $layans = Layan::orderBy('urutan')->paginate(10);
        $counts = [
            'total'  => Layan::count(),
            'aktif'  => Layan::where('aktif', true)->count(),
            'nonaktif' => Layan::where('aktif', false)->count(),
        ];

        return view('dashboard.staff.cms.layan.index', compact('layans', 'counts'));
    }

    /* ---------------------------------------------------------------- */
    /*  Create / Store                                                  */
    /* ---------------------------------------------------------------- */

    public function create()
    {
        return view('dashboard.staff.cms.layan.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'      => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:1000',
            'icon'      => 'nullable|string|max:100',
            'urutan'    => 'nullable|integer|min:0',
        ]);

        $data['urutan'] = $data['urutan'] ?? Layan::max('urutan') + 1;

        Layan::create($data);

        return redirect()->route('dashboard.admin.cms.layan.index')
            ->with('success', 'Layanan berhasil ditambahkan.');
    }

    /* ---------------------------------------------------------------- */
    /*  Edit / Update                                                   */
    /* ---------------------------------------------------------------- */

    public function edit(Layan $layan)
    {
        return view('dashboard.staff.cms.layan.edit', ['layan' => $layan]);
    }

    public function update(Request $request, Layan $layan)
    {
        $data = $request->validate([
            'nama'      => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:1000',
            'icon'      => 'nullable|string|max:100',
            'urutan'    => 'nullable|integer|min:0',
        ]);

        $layan->update($data);

        return redirect()->route('dashboard.admin.cms.layan.index')
            ->with('success', 'Layanan berhasil diperbarui.');
    }

    /* ---------------------------------------------------------------- */
    /*  Destroy                                                         */
    /* ---------------------------------------------------------------- */

    public function destroy(Layan $layan)
    {
        $layan->delete();

        return redirect()->route('dashboard.admin.cms.layan.index')
            ->with('success', 'Layanan berhasil dihapus.');
    }
}
