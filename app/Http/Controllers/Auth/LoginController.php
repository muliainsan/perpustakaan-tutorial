<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Tampilkan formulir login.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi sesi manual dengan rate limiting dan regenerasi sesi.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $throttleKey = Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ])->withInput($request->only('email'))->setStatusCode(429);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);

            // Lindungi dari Session Fixation
            $request->session()->regenerate();

            $user = Auth::user();
            if (in_array($user->role, ['admin', 'petugas'], true)) {
                return redirect()->intended(route('admin.books.index'))->with('success', 'Selamat datang kembali, ' . $user->name . '!');
            }

            return redirect()->intended(route('catalog.index'))->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->withInput($request->only('email'));
    }

    /**
     * Logout aman dengan membatalkan sesi dan meregenerasi CSRF token.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalog.index')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}

