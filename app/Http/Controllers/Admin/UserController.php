<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan daftar seluruh pengguna perpustakaan (Admin only).
     */
    public function index(Request $request): View
    {
        $users = User::when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('member_code', 'like', "%{$search}%");
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->where('role', $request->input('role'));
            })
            ->latest()
            ->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Perbarui peran pengguna (Admin only).
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,petugas,anggota'],
        ], [
            'role.required' => 'Peran pengguna wajib dipilih.',
            'role.in'       => 'Pilihan peran tidak valid.',
        ]);

        // Pencegahan agar admin saat ini tidak menurunkan perannya sendiri tanpa sengaja
        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()->with('error', 'Anda tidak dapat mencabut peran admin dari akun Anda sendiri.');
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "Peran pengguna {$user->name} berhasil diperbarui menjadi {$user->role}.");
    }
}

