<?php

namespace App\Policies;

use App\Enums\UserPermission;
use App\Models\MemberAssembly;
use App\Models\User;

class MemberAssemblyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && ! $user->hasPendingRegistrationApproval();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission(UserPermission::ManageMemberAssemblies);
    }

    public function view(User $user, MemberAssembly $assembly): bool
    {
        return $this->create($user) || ($this->viewAny($user) && $assembly->published_at !== null && $user->member()->exists());
    }

    public function update(User $user, MemberAssembly $assembly): bool
    {
        return $this->create($user) && ! $assembly->published_at && ! $assembly->archived_at;
    }

    public function publish(User $user, MemberAssembly $assembly): bool
    {
        return $this->update($user, $assembly);
    }

    public function attend(User $user, MemberAssembly $assembly): bool
    {
        return $this->view($user, $assembly) && $assembly->isOpen() && $user->member()->exists();
    }

    public function vote(User $user, MemberAssembly $assembly): bool
    {
        return $this->attend($user, $assembly);
    }

    public function finalize(User $user, MemberAssembly $assembly): bool
    {
        return $this->create($user) && $assembly->isOpen();
    }

    public function archive(User $user, MemberAssembly $assembly): bool
    {
        return $this->create($user) && ! $assembly->archived_at && ($assembly->finalized_at || ! $assembly->published_at);
    }
}
