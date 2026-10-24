<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
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
}
