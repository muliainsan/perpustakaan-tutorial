<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Autentikasi API via Bearer Token Sanctum dengan proteksi rate limit (maks 5 req/menit).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $throttleKey = 'api-login:' . Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ], 429);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'Email atau kata sandi tidak valid.',
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        // Buat token Sanctum baru
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message'    => 'Login berhasil.',
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'member_code' => $user->member_code,
            ],
        ]);
    }

    /**
     * Cabut (revoke) token autentikasi Sanctum aktif.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil. Token telah dicabut.',
        ]);
    }

    /**
     * Ambil data profil pengguna yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'member_code' => $user->member_code,
                'phone'       => $user->phone,
            ],
        ]);
    }
}

