<?php

namespace App\Policies;

use App\Enums\MeterStatus;
use App\Models\Meter;
use App\Models\User;

class MeterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAllMeters() || $user->member()->exists();
    }

    public function view(User $user, Meter $meter): bool
    {
        if ($user->canViewAllMeters()) {
            return true;
        }

        return $meter->parcel->tenancies()
            ->activeOn()
            ->whereHas('member', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->canManageMeters();
    }

    public function update(User $user, Meter $meter): bool
    {
        return $user->canManageMeters();
    }

    public function replace(User $user, Meter $meter): bool
    {
        return $user->canManageMeters();
    }

    public function submitReading(User $user, Meter $meter): bool
    {
        if (! $user->hasTenantAccess() || $meter->status !== MeterStatus::Active) {
            return false;
        }

        return $meter->parcel->tenancies()
            ->activeOn()
            ->whereHas('member', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }
}
