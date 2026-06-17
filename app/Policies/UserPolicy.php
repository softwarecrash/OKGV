<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() || $user->role === UserRole::Board;
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

    public function updateAccess(User $user, User $subject): bool
    {
        if ($user->is($subject)) {
            return false;
        }

        if ($user->isAdministrator()) {
            return true;
        }

        return $user->role === UserRole::Board
            && ! $subject->isAdministrator()
            && in_array($subject->role, [
                UserRole::Board,
                UserRole::Tenant,
            ], true);
    }

    public function delete(User $user, User $subject): bool
    {
        if (! $user->isAdministrator() || $user->is($subject)) {
            return false;
        }

        if ($subject->isAdministrator()) {
            return User::query()->where('is_system_admin', true)->count() > 1;
        }

        return true;
    }
}
