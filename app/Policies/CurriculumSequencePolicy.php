<?php

namespace App\Policies;

use App\Models\CurriculumSequence;
use App\Models\User;

class CurriculumSequencePolicy
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

    public function view(User $user, CurriculumSequence $sequence): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CurriculumSequence $sequence): bool
    {
        return false;
    }

    public function delete(User $user, CurriculumSequence $sequence): bool
    {
        return false;
    }

    public function restore(User $user, CurriculumSequence $sequence): bool
    {
        return false;
    }

    public function forceDelete(User $user, CurriculumSequence $sequence): bool
    {
        return false;
    }
}
