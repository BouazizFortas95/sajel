<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Grades are only reached through the Assessment relation manager.
     *
     * Filament calls this ability with the model class only, so the owning
     * assessment cannot be inspected here. Parent ownership is enforced by
     * view()/update() and by GradesRelationManager::canViewForRecord().
     */
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Grade $grade): bool
    {
        return $this->ownsGrade($user, $grade);
    }

    /**
     * Rows are materialized with insertOrIgnore(), never created manually.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Grade $grade): bool
    {
        return $this->ownsGrade($user, $grade);
    }

    /**
     * Rows are removed by database cascades, never individually.
     */
    public function delete(User $user, Grade $grade): bool
    {
        return false;
    }

    /**
     * A grade belongs to the teacher who owns its assessment's schedule.
     */
    private function ownsGrade(User $user, Grade $grade): bool
    {
        return $user->isTeacher()
            && $grade->assessment?->schedule?->user_id === $user->id;
    }
}
