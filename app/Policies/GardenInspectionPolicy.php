<?php

namespace App\Policies;

use App\Enums\FeatureModule;
use App\Enums\UserPermission;
use App\Models\GardenInspection;
use App\Models\GardenInspectionFinding;
use App\Models\User;

class GardenInspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return FeatureModule::GardenInspections->enabled() && $user->hasVerifiedEmail() && ! $user->hasPendingRegistrationApproval() && ($user->hasPermission(UserPermission::ManageGardenInspections) || $user->member()->exists());
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission(UserPermission::ManageGardenInspections);
    }

    public function view(User $user, GardenInspection $inspection): bool
    {
        return $this->create($user) || $inspection->findings()
            ->whereHas('parcel', fn ($query) => $query->whereHas('tenancies', fn ($query) => $query
                ->activeOn()
                ->whereHas('member', fn ($query) => $query->where('user_id', $user->id))))
            ->exists();
    }

    public function viewFinding(User $user, GardenInspectionFinding $finding): bool
    {
        return $this->create($user) || $finding->parcel->tenancies()->activeOn()->whereHas('member', fn ($q) => $q->where('user_id', $user->id))->exists();
    }

    public function update(User $user, GardenInspection $inspection): bool
    {
        return $this->create($user) && ! $inspection->isFinalized();
    }

    public function finalize(User $user, GardenInspection $inspection): bool
    {
        return $this->update($user, $inspection);
    }

    public function resolve(User $user, GardenInspectionFinding $finding): bool
    {
        return $this->create($user) && ! $finding->inspection->isFinalized();
    }
}
