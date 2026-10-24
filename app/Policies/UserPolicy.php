<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->isAdmin();
    }
}
