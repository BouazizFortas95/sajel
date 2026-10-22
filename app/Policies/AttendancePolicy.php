<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Attendances are only reached through the DailyLog relation manager.
     *
     * Filament calls this ability with the model class only, so the owning
     * daily log cannot be inspected here. Parent ownership is enforced by
     * view()/update() and by AttendancesRelationManager::canViewForRecord().
     */
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $this->ownsAttendance($user, $attendance);
    }

    /**
     * Rows are materialized with insertOrIgnore(), never created manually.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $this->ownsAttendance($user, $attendance);
    }

    /**
     * Rows are removed by database cascades, never individually.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return false;
    }

    /**
     * An attendance belongs to the teacher who owns its daily log's schedule.
     */
    private function ownsAttendance(User $user, Attendance $attendance): bool
    {
        return $user->isTeacher()
            && $attendance->dailyLog?->schedule?->user_id === $user->id;
    }
}
