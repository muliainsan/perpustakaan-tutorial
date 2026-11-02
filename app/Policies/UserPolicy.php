<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function updateRole(User $user, User $model): bool
    {
        return $user->role === 'admin';
    }
}

