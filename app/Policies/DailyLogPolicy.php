<?php

namespace App\Policies;

use App\Models\DailyLog;
use App\Models\User;

class DailyLogPolicy
{
    /**
     * Admins bypass general checks, EXCEPT creating daily logs,
     * which is reserved exclusively for teachers conducting lessons.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isDeveloper() && in_array($ability, ['create', 'update', 'delete', 'restore', 'forceDelete'], true)) {
            return false;
        }

        if ($ability === 'create' && $user->isAdmin()) {
            return false;
        }

        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DailyLog $dailyLog): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($dailyLog->substitute_teacher_id === $user->id) {
            return true;
        }

        return $dailyLog->schedule?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, DailyLog $dailyLog): bool
    {
        if ($dailyLog->substitute_teacher_id === $user->id) {
            return true;
        }

        return $dailyLog->schedule?->user_id === $user->id;
    }

    public function delete(User $user, DailyLog $dailyLog): bool
    {
        return $dailyLog->schedule?->user_id === $user->id;
    }
}
