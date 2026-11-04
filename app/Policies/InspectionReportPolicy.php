<?php

namespace App\Policies;

use App\Models\InspectionReport;
use App\Models\User;

class InspectionReportPolicy
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

    public function view(User $user, InspectionReport $inspectionReport): bool
    {
        return $inspectionReport->teacher_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, InspectionReport $inspectionReport): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, InspectionReport $inspectionReport): bool
    {
        return $user->isAdmin();
    }

    public function print(User $user, InspectionReport $inspectionReport): bool
    {
        return $inspectionReport->teacher_id === $user->id;
    }
}
