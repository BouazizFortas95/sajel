<?php

namespace App\Policies;

use App\Models\LabStation;
use App\Models\User;

class LabStationPolicy
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
        return false;
    }

    public function view(User $user, LabStation $station): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LabStation $station): bool
    {
        return false;
    }

    public function delete(User $user, LabStation $station): bool
    {
        return false;
    }

    public function restore(User $user, LabStation $station): bool
    {
        return false;
    }

    public function forceDelete(User $user, LabStation $station): bool
    {
        return false;
    }
}
