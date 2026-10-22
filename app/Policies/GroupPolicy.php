<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * The group catalog is an administrative screen.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Group $group): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Group $group): bool
    {
        return false;
    }

    public function delete(User $user, Group $group): bool
    {
        return false;
    }

    public function restore(User $user, Group $group): bool
    {
        return false;
    }

    public function forceDelete(User $user, Group $group): bool
    {
        return false;
    }
}
