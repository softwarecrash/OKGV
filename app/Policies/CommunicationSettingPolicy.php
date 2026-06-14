<?php

namespace App\Policies;

use App\Models\User;

class CommunicationSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCommunication();
    }

    public function update(User $user): bool
    {
        return $user->canManageCommunication();
    }

    public function test(User $user): bool
    {
        return $user->canManageCommunication();
    }
}
