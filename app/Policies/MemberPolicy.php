<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAllMasterData() || $user->member()->exists();
    }

    public function view(User $user, Member $member): bool
    {
        return $user->canViewAllMasterData() || $member->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->canManageMasterData();
    }

    public function update(User $user, Member $member): bool
    {
        return $user->canManageMasterData();
    }

    public function archive(User $user, Member $member): bool
    {
        return $user->canManageMasterData();
    }
}
