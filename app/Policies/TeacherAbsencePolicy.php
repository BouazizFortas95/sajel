<?php

namespace App\Policies;

use App\Models\TeacherAbsence;
use App\Models\User;

class TeacherAbsencePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TeacherAbsence $absence): bool
    {
        return $absence->teacher_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TeacherAbsence $absence): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TeacherAbsence $absence): bool
    {
        return $user->isAdmin();
    }

    public function print(User $user, TeacherAbsence $absence): bool
    {
        return $absence->teacher_id === $user->id;
    }
}

