<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    public const SESSION_KEY = 'impersonated_by';

    public const TIMESTAMP_KEY = 'impersonated_at';

    /**
     * Check if a given user has the authority to impersonate teachers.
     */
    public function canImpersonate(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isDeveloper() || $user->isPrincipal() || $user->isAdmin();
    }

    /**
     * Check if a target user can be impersonated.
     */
    public function canBeImpersonated(User $target, ?User $actor = null): bool
    {
        $actor = $actor ?: Auth::user();

        // Cannot impersonate yourself
        if ($actor && $actor->id === $target->id) {
            return false;
        }

        // Only teachers can be impersonated
        return $target->isTeacher();
    }

    /**
     * Start impersonating the target teacher.
     */
    public function impersonate(User $target): void
    {
        $actor = Auth::user();
        abort_unless($this->canImpersonate($actor), 403);
        abort_unless($this->canBeImpersonated($target, $actor), 403);

        session()->put(self::SESSION_KEY, $actor->id);
        session()->put(self::TIMESTAMP_KEY, now()->timestamp);

        Auth::login($target);
    }

    /**
     * Check if the current session is an active impersonation.
     */
    public function isImpersonating(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    /**
     * Retrieve the original user who initiated the impersonation.
     */
    public function getOriginalUser(): ?User
    {
        if (! $this->isImpersonating()) {
            return null;
        }

        $id = session()->get(self::SESSION_KEY);

        return $id ? User::find($id) : null;
    }

    /**
     * Terminate the impersonation session and restore the original user.
     */
    public function leave(): ?User
    {
        if (! $this->isImpersonating()) {
            return null;
        }

        $originalId = session()->pull(self::SESSION_KEY);
        session()->forget(self::TIMESTAMP_KEY);

        if (! $originalId) {
            return null;
        }

        $originalUser = User::find($originalId);

        if ($originalUser) {
            Auth::login($originalUser);
        }

        return $originalUser;
    }
}
