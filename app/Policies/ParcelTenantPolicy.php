<?php

namespace App\Policies;

use App\Models\ParcelTenant;
use App\Models\User;

class ParcelTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAllMasterData() || $user->member()->exists();
    }

    public function view(User $user, ParcelTenant $parcelTenant): bool
    {
        return $user->canViewAllMasterData()
            || $parcelTenant->member()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->canManageMasterData();
    }

    public function update(User $user, ParcelTenant $parcelTenant): bool
    {
        return $user->canManageMasterData();
    }
}
