<?php

namespace App\Policies;

use App\Models\Meter;
use App\Models\User;

class MeterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canViewAllMeters() || $user->member()->exists();
    }

    public function view(User $user, Meter $meter): bool
    {
        if ($user->role->canViewAllMeters()) {
            return true;
        }

        return $meter->parcel->tenancies()
            ->whereHas('member', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->role->canManageMeters();
    }

    public function update(User $user, Meter $meter): bool
    {
        return $user->role->canManageMeters();
    }

    public function replace(User $user, Meter $meter): bool
    {
        return $user->role->canManageMeters();
    }
}
