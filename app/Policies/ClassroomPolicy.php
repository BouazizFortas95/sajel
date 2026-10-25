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
     * Classroom management is an administrative screen. Declaring these
     * abilities prevents Filament's missing-method fallback from allowing
     * teachers through.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Classroom $classroom): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            return $classroom->groups()
                ->whereHas('schedules', fn ($query) => $query->where('user_id', $user->id))
                ->exists()
                || $classroom->schedules()
                    ->where('user_id', $user->id)
                    ->exists();
        }

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
