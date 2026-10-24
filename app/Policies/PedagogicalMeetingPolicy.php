<?php

namespace App\Policies;

use App\Models\PedagogicalMeeting;
use App\Models\User;

class PedagogicalMeetingPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PedagogicalMeeting $meeting): bool
    {
        return $meeting->user_id === $user->id || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }

    public function update(User $user, PedagogicalMeeting $meeting): bool
    {
        return $meeting->user_id === $user->id || $user->isAdmin();
    }

    public function delete(User $user, PedagogicalMeeting $meeting): bool
    {
        return $meeting->user_id === $user->id || $user->isAdmin();
    }
}
