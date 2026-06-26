<?php

namespace App\Http\Controllers\Web;

use App\Models\CaseModel;
use App\Models\CmsBlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PA Staff Controller
    |--------------------------------------------------------------------------
    |
    | Dashboard, activities, dan archive untuk role pa_staff.
    | CMS CRUD halaman publik dikelola oleh Admin CMS Controllers.
    |
    */

    // ── Dashboard Staff ────────────────────────────────────────────────────

    public function index()
    {
        $institutionId = Auth::user()->institution_id;

        $stats = [
            'active_cases'  => CaseModel::where('institution_id', $institutionId)
                ->whereIn('status', ['PENDING_REVIEW', 'PENDING_OCR', 'PENDING_PA_VERIFICATION', 'PENDING_DISDUKCAPIL'])
                ->count(),
            'archived'      => CaseModel::where('institution_id', $institutionId)
                ->whereIn('status', ['COMPLETED', 'ARCHIVED'])
                ->count(),
            'total'         => CaseModel::where('institution_id', $institutionId)->count(),
            'blogs'         => CmsBlogPost::count(),
        ];

        $recentActivities = CaseModel::where('institution_id', $institutionId)
            ->with(['institution', 'assignedPaUser', 'publicSubmission'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('dashboard.staff.index', compact('stats', 'recentActivities'));
    }

    // ── Aktivitas Terbaru ──────────────────────────────────────────────────

    public function activities(Request $request)
    {
        $user = Auth::user();
        $institutionId = $user->institution_id;

        $query = CaseModel::where('institution_id', $institutionId)
            ->with(['institution', 'assignedPaUser', 'publicSubmission'])
            ->orderByDesc('created_at');

        $status = $request->input('status');
        if ($status) {
            $query->where('status', $status);
        }

        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                    ->orWhere('petitioner_name', 'like', "%{$search}%")
                    ->orWhere('petitioner_nik', 'like', "%{$search}%");
            });
        }

        $cases = $query->paginate(20)->withQueryString();

        return view('dashboard.staff.activities', compact('cases'));
    }

    // ── Arsip ─────────────────────────────────────────────────────────────

    public function archive(Request $request)
    {
        $user = Auth::user();
        $institutionId = $user->institution_id;

        $query = CaseModel::where('institution_id', $institutionId)
            ->with(['institution', 'assignedPaUser', 'publicSubmission'])
            ->whereIn('status', ['COMPLETED', 'ARCHIVED'])
            ->orderByDesc('updated_at');

        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                    ->orWhere('petitioner_name', 'like', "%{$search}%")
                    ->orWhere('petitioner_nik', 'like', "%{$search}%");
            });
        }

        $cases = $query->paginate(20)->withQueryString();

        return view('dashboard.staff.archive', compact('cases'));
    }

    // ── Arsip Case Detail ─────────────────────────────────────────────────

    public function archiveShow($id)
    {
        $case = CaseModel::with(['documents', 'transitions', 'ocrValidations'])
            ->findOrFail($id);

        return view('dashboard.staff.archive-show', compact('case'));
    }
}
