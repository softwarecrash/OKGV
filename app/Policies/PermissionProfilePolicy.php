<?php

namespace App\Policies;

use App\Models\PermissionProfile;
use App\Models\User;

class PermissionProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, PermissionProfile $profile): bool
    {
        return $user->isAdministrator();
    }
}
