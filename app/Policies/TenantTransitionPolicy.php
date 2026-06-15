<?php

namespace App\Policies;

use App\Models\TenantTransition;
use App\Models\User;

class TenantTransitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAllMasterData();
    }

    public function view(User $user, TenantTransition $tenantTransition): bool
    {
        return $user->canViewAllMasterData();
    }

    public function create(User $user): bool
    {
        return $user->canManageMasterData();
    }
}
