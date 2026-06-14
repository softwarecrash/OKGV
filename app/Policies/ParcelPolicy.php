<?php

namespace App\Policies;

use App\Models\Parcel;
use App\Models\User;

class ParcelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canViewAllMasterData() || $user->member()->exists();
    }

    public function view(User $user, Parcel $parcel): bool
    {
        if ($user->role->canViewAllMasterData()) {
            return true;
        }

        return $parcel->tenancies()
            ->activeOn()
            ->whereHas('member', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->role->canManageMasterData();
    }

    public function update(User $user, Parcel $parcel): bool
    {
        return $user->role->canManageMasterData();
    }
}
