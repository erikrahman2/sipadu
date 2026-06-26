<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jika pengguna sudah login (sebagai staf/admin) lalu mengakses halaman publik
 * (/pengajuan, /tracking, /lacak, dll), middleware ini akan otomatis me-logout
 * mereka dan mengulangi request ke halaman yang sama agar warga bisa mengakses
 * halaman tersebut tanpa sesi staf yang mengganggu.
 */
class AutoLogoutOnPublicPage
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Jangan auto-logout jika user sudah login sebagai staf/admin
            if ($user->hasRole('super_admin') ||
                $user->hasRole('pa_assistant') ||
                $user->hasRole('pa_management') ||
                $user->hasRole('pa_staff') ||
                $user->hasRole('disdukcapil_staff')) {
                return $next($request);
            }

            // Catat ke audit log
            $this->audit->log($user, 'auth.auto_logout_public_page', 'User', $user?->id,
                null,
                ['reason' => 'Akses halaman publik saat sudah login sebagai staf', 'url' => $request->url()],
                $request
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect kembali ke halaman yang sama (sekarang tanpa sesi)
            return redirect($request->fullUrl());
        }

        return $next($request);
    }
}
