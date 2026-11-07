<?php

namespace App\Policies;

use App\Models\EducationalInstitution;
use App\Models\User;

class EducationalInstitutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageInstitutions();
    }

    public function view(User $user, EducationalInstitution $institution): bool
    {
        return $user->canManageInstitutions();
    }

    public function create(User $user): bool
    {
        return $user->canManageInstitutions();
    }

    public function update(User $user, EducationalInstitution $institution): bool
    {
        return $user->canManageInstitutions();
    }

    public function delete(User $user, EducationalInstitution $institution): bool
    {
        return $user->canManageInstitutions();
    }
}
