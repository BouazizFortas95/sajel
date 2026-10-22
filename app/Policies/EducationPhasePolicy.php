<?php

namespace App\Policies;

use App\Models\EducationPhase;
use App\Models\User;

class EducationPhasePolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * The education phase catalog is an administrative screen.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, EducationPhase $educationPhase): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, EducationPhase $educationPhase): bool
    {
        return false;
    }

    public function delete(User $user, EducationPhase $educationPhase): bool
    {
        return false;
    }

    public function restore(User $user, EducationPhase $educationPhase): bool
    {
        return false;
    }

    public function forceDelete(User $user, EducationPhase $educationPhase): bool
    {
        return false;
    }
}
