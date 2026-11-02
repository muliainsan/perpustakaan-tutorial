<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Tampilkan formulir pendaftaran anggota perpustakaan.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Tangani pendaftaran anggota baru, hash password, generate kode anggota, dan regenerasi sesi.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone'    => ['nullable', 'string', 'max:20'],
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format alamat email tidak valid.',
            'email.unique'       => 'Alamat email sudah terdaftar dalam sistem.',
            'password.required'  => 'Kata sandi wajib diisi.',
            'password.min'       => 'Kata sandi minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'phone.max'          => 'Nomor telepon maksimal 20 digit.',
        ]);

        $nextId = (User::max('id') ?? 0) + 1;
        $memberCode = sprintf('MBR-%05d', $nextId);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'role'        => 'anggota',
            'member_code' => $memberCode,
            'phone'       => $validated['phone'] ?? null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('catalog.index')
            ->with('success', "Selamat datang! Akun anggota Anda berhasil dibuat dengan nomor anggota: {$memberCode}");
    }
}

