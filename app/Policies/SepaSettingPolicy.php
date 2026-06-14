<?php

namespace App\Policies;

use App\Models\User;

class SepaSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageSepa();
    }

    public function update(User $user): bool
    {
        return $user->canManageSepa();
    }
}
