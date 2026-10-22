<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $this->ownsAssessment($user, $assessment);
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $this->ownsAssessment($user, $assessment);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $this->ownsAssessment($user, $assessment);
    }

    /**
     * An assessment belongs to the teacher who owns its schedule.
     */
    private function ownsAssessment(User $user, Assessment $assessment): bool
    {
        return $user->isTeacher()
            && $assessment->schedule?->user_id === $user->id;
    }
}
