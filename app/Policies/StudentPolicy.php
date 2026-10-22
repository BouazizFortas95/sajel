<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentPolicy
{
    /**
     * Admins bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * The master student list is an administrative screen.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * A teacher may only view students on their own roster.
     *
     * The check mirrors Student::scopeForSchedule(): a schedule attached to a
     * group targets that group, while a classroom-only schedule targets every
     * group in that classroom.
     */
    public function view(User $user, Student $student): bool
    {
        if (! $user->isTeacher()) {
            return false;
        }

        $group = $student->group;

        if (! $group) {
            return false;
        }

        return $user->schedules()
            ->where(function (Builder $query) use ($group): void {
                $query
                    ->where('group_id', $group->id)
                    ->orWhere(function (Builder $query) use ($group): void {
                        $query
                            ->whereNull('group_id')
                            ->where('classroom_id', $group->classroom_id);
                    });
            })
            ->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Student $student): bool
    {
        return false;
    }

    public function delete(User $user, Student $student): bool
    {
        return false;
    }

    public function restore(User $user, Student $student): bool
    {
        return false;
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return false;
    }
}
