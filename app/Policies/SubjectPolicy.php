<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * The subject catalog is an administrative screen.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Subject $subject): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Subject $subject): bool
    {
        return false;
    }

    public function delete(User $user, Subject $subject): bool
    {
        return false;
    }

    public function restore(User $user, Subject $subject): bool
    {
        return false;
    }

    public function forceDelete(User $user, Subject $subject): bool
    {
        return false;
    }
}
