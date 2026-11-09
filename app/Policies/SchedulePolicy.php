<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    /**
     * Developers are strictly restricted from modifying educational schedules.
     * Principals, Censors, and Digitalization Admins have schedule management authority.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isDeveloper() && in_array($ability, ['create', 'update', 'delete', 'restore', 'forceDelete'], true)) {
            return false;
        }

        if ($user->isPrincipal() || $user->isCensor() || $user->role === Role::Admin) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $schedule->user_id === $user->id || $user->isPrincipal() || $user->isCensor() || $user->role === Role::Admin;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher() || $user->isPrincipal() || $user->isCensor() || $user->role === Role::Admin;
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $schedule->user_id === $user->id || $user->isPrincipal() || $user->isCensor() || $user->role === Role::Admin;
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $schedule->user_id === $user->id || $user->isPrincipal() || $user->isCensor() || $user->role === Role::Admin;
    }
}
