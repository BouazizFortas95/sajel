<?php

namespace App\Policies;

use App\Models\DailyLog;
use App\Models\User;

class DailyLogPolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DailyLog $dailyLog): bool
    {
        if ($dailyLog->substitute_teacher_id === $user->id) {
            return true;
        }

        return $dailyLog->schedule?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
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
