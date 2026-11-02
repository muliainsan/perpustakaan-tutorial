<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar buku.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail buku.
     */
    public function view(?User $user, Book $book): bool
    {
        return true;
    }

    /**
     * Tentukan apakah pengguna dapat membuat entri buku baru.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'petugas'], true);
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui data buku.
     */
    public function update(User $user, Book $book): bool
    {
        return in_array($user->role, ['admin', 'petugas'], true);
    }

    /**
     * Tentukan apakah pengguna dapat menghapus buku.
     */
    public function delete(User $user, Book $book): bool
    {
        return in_array($user->role, ['admin', 'petugas'], true);
    }
}

