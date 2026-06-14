<?php

namespace App\Policies;

use App\Models\SepaMandate;
use App\Models\User;

class SepaMandatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageSepa();
    }

    public function create(User $user): bool
    {
        return $user->canManageSepa();
    }

    public function update(User $user, SepaMandate $mandate): bool
    {
        return $user->canManageSepa();
    }
}
