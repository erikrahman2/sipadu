<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    private function homeRouteFor($user): string
    {
        if ($user?->hasRole('super_admin')) {
            return 'dashboard.admin.users';
        }

        return 'dashboard.index';
    }

    public function showLogin()
    {
        return auth()->check()
            ? redirect()->route($this->homeRouteFor(auth()->user()))
            : view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Kredensial tidak valid.'])->onlyInput('email');
        }

        $user = Auth::user();
        if ($user->status !== 'active') {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun tidak aktif.']);
        }

        $this->audit->log($user, 'auth.login', 'User', $user->id, null, null, $request);
        $request->session()->regenerate();

        return redirect()->intended(route($this->homeRouteFor($user)));
    }

    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            
            if ($user) {
                $this->audit->log($user, 'auth.logout', 'User', $user->id, null, null, $request);
            }
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('home');
        } catch (\Exception $e) {
            // Jika ada error (session tidak valid, dll), tetap logout dan redirect
            Auth::logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            
            return redirect()->route('home');
        }
    }
}
