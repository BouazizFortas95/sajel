<?php

namespace App\Policies;

use App\Models\SchoolHoliday;
use App\Models\User;

class SchoolHolidayPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SchoolHoliday $holiday): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SchoolHoliday $holiday): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, SchoolHoliday $holiday): bool
    {
        return $user->isAdmin();
    }
}