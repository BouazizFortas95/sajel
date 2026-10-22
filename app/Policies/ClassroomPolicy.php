<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * The classroom catalog is an administrative screen.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Classroom $classroom): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return false;
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return false;
    }

    public function restore(User $user, Classroom $classroom): bool
    {
        return false;
    }

    public function forceDelete(User $user, Classroom $classroom): bool
    {
        return false;
    }
}
