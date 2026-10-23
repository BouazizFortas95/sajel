<?php

namespace App\Policies;

use App\Models\PedagogicalMemo;
use App\Models\User;

class PedagogicalMemoPolicy
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

    public function view(User $user, PedagogicalMemo $memo): bool
    {
        return $this->ownsMemo($user, $memo);
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, PedagogicalMemo $memo): bool
    {
        return $this->ownsMemo($user, $memo);
    }

    public function delete(User $user, PedagogicalMemo $memo): bool
    {
        return $this->ownsMemo($user, $memo);
    }

    public function duplicate(User $user, PedagogicalMemo $memo): bool
    {
        return $this->ownsMemo($user, $memo);
    }

    public function archive(User $user, PedagogicalMemo $memo): bool
    {
        return $this->ownsMemo($user, $memo);
    }

    public function print(User $user, PedagogicalMemo $memo): bool
    {
        return $this->ownsMemo($user, $memo);
    }

    public function downloadPdf(User $user, PedagogicalMemo $memo): bool
    {
        return $this->ownsMemo($user, $memo);
    }

    /**
     * A memo belongs to the teacher who created it.
     */
    private function ownsMemo(User $user, PedagogicalMemo $memo): bool
    {
        return $user->isTeacher()
            && $memo->user_id === $user->id;
    }
}
