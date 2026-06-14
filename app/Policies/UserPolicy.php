<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, User $subject): bool
    {
        return $user->isAdministrator() || $user->is($subject);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, User $subject): bool
    {
        return $user->isAdministrator() || $user->is($subject);
    }

    public function delete(User $user, User $subject): bool
    {
        return $user->isAdministrator() && ! $user->is($subject);
    }
}
