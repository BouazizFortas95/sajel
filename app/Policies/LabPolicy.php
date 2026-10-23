<?php

namespace App\Policies;

use App\Models\Lab;
use App\Models\User;

class LabPolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * The lab catalog is an administrative screen.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Lab $lab): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Lab $lab): bool
    {
        return false;
    }

    public function delete(User $user, Lab $lab): bool
    {
        return false;
    }

    public function restore(User $user, Lab $lab): bool
    {
        return false;
    }

    public function forceDelete(User $user, Lab $lab): bool
    {
        return false;
    }
}
