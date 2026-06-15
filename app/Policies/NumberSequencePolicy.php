<?php

namespace App\Policies;

use App\Models\User;

class NumberSequencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user): bool
    {
        return $user->isAdministrator();
    }
}
