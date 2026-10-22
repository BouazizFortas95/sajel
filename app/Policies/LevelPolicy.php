<?php

namespace App\Policies;

use App\Models\Level;
use App\Models\User;

class LevelPolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * The level catalog is an administrative screen.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Level $level): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Level $level): bool
    {
        return false;
    }

    public function delete(User $user, Level $level): bool
    {
        return false;
    }

    public function restore(User $user, Level $level): bool
    {
        return false;
    }

    public function forceDelete(User $user, Level $level): bool
    {
        return false;
    }
}
