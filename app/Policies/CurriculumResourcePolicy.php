<?php

namespace App\Policies;

use App\Models\CurriculumResource;
use App\Models\User;

class CurriculumResourcePolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * The curriculum catalog is an administrative screen.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, CurriculumResource $resource): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CurriculumResource $resource): bool
    {
        return false;
    }

    public function delete(User $user, CurriculumResource $resource): bool
    {
        return false;
    }

    public function restore(User $user, CurriculumResource $resource): bool
    {
        return false;
    }

    public function forceDelete(User $user, CurriculumResource $resource): bool
    {
        return false;
    }
}
