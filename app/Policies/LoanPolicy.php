<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar peminjaman.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail peminjaman spesifik (Pencegahan IDOR).
     */
    public function view(User $user, Loan $loan): bool
    {
        if (in_array($user->role, ['admin', 'petugas'], true)) {
            return true;
        }

        return $user->id === $loan->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat membuat transaksi peminjaman baru.
     */
    public function create(User $user, ?int $targetUserId = null): bool
    {
        if (in_array($user->role, ['admin', 'petugas'], true)) {
            return true;
        }

        // Anggota hanya boleh meminjam atas nama dirinya sendiri
        return $targetUserId === null || $user->id === $targetUserId;
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui data transaksi peminjaman.
     */
    public function update(User $user, Loan $loan): bool
    {
        return in_array($user->role, ['admin', 'petugas'], true);
    }

    /**
     * Tentukan apakah pengguna dapat mencatat pengembalian buku.
     */
    public function returnBooks(User $user, Loan $loan): bool
    {
        return in_array($user->role, ['admin', 'petugas'], true);
    }

    /**
     * Tentukan apakah pengguna dapat mencatat pelunasan denda.
     */
    public function payFine(User $user, Loan $loan): bool
    {
        return in_array($user->role, ['admin', 'petugas'], true);
    }
}

