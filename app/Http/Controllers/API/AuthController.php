<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    // ─────────────────────────────────────────────────────────────────────────
    // POST /auth/login
    // ─────────────────────────────────────────────────────────────────────────

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                $this->audit->log(null, 'auth.login_failed', null, null, null, ['email' => $request->email]);
                return response()->json(['message' => 'Kredensial tidak valid.'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Gagal membuat token.'], 500);
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            JWTAuth::invalidate($token);
            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        $this->audit->log($user, 'auth.login', 'User', $user->id);

        return $this->respondWithToken($token, $user);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /auth/logout
    // ─────────────────────────────────────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $user = auth()->user();
        JWTAuth::invalidate(JWTAuth::getToken());
        $this->audit->log($user, 'auth.logout', 'User', $user->id);
        return response()->json(['message' => 'Berhasil logout.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /auth/refresh
    // ─────────────────────────────────────────────────────────────────────────

    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return $this->respondWithToken($token, auth()->user());
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token tidak dapat di-refresh.'], 401);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /auth/me
    // ─────────────────────────────────────────────────────────────────────────

    public function me(): JsonResponse
    {
        $user = auth()->user();
        return response()->json([
            'user'        => $user,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function respondWithToken(string $token, $user): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'roles'          => $user->getRoleNames(),
                'institution_id' => $user->institution_id,
            ],
        ]);
    }
}
