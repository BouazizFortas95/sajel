<?php

namespace App\Policies;

use App\Models\User;
use Filament\Actions\Exports\Models\Export;

class ExportPolicy
{
    /**
     * Export files contain the owner's register data, so only the owner and
     * administrators may download them.
     */
    public function view(User $user, Export $export): bool
    {
        return $user->isAdmin() || (int) $export->user_id === (int) $user->getKey();
    }
}
