<?php

namespace App\Policies;

use App\Models\BehaviorObservation;
use App\Models\User;

class BehaviorObservationPolicy
{
    /**
     * Admins and leadership bypass general checks,
     * while developers are restricted from mutating pedagogical data.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isDeveloper() && in_array($ability, ['create', 'update', 'delete', 'restore', 'forceDelete'], true)) {
            return false;
        }

        if ($user->isAdmin() || $user->isPrincipal() || $user->isCensor()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BehaviorObservation $observation): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isTeachingStaff() || $user->isLeadership() || $user->isEducationStaff();
    }

    public function update(User $user, BehaviorObservation $observation): bool
    {
        if ($user->isLeadership() || $user->isGeneralSupervisor()) {
            return true;
        }

        // A teacher can only edit their own recorded observation
        return $observation->recorded_by_id === $user->id;
    }

    /**
     * Soft delete (send to trash).
     * Teachers can only send their own reports to trash.
     */
    public function delete(User $user, BehaviorObservation $observation): bool
    {
        if ($user->isLeadership() || $user->isGeneralSupervisor()) {
            return true;
        }

        return $observation->recorded_by_id === $user->id;
    }

    /**
     * Restore from trash.
     * Reserved for administrative leadership and general supervisor.
     */
    public function restore(User $user, BehaviorObservation $observation): bool
    {
        return $user->isLeadership() || $user->isGeneralSupervisor();
    }

    /**
     * Permanent / Force deletion.
     * Strictly reserved for administrative ranks who visa/approve reports (Principal, Censor, Counselor/Supervisor, Admin).
     * Teachers can NEVER permanently delete reports on their own.
     */
    public function forceDelete(User $user, BehaviorObservation $observation): bool
    {
        return $user->isLeadership() || $user->isGeneralSupervisor();
    }
}
