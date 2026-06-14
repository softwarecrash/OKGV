<?php

namespace App\Policies;

use App\Models\Letter;
use App\Models\User;

class LetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCommunication();
    }

    public function view(User $user, Letter $letter): bool
    {
        return $user->canManageCommunication();
    }

    public function create(User $user): bool
    {
        return $user->canManageCommunication();
    }
}
