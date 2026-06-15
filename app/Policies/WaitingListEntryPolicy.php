<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaitingListEntry;

class WaitingListEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageWaitingList();
    }

    public function view(User $user, WaitingListEntry $waitingListEntry): bool
    {
        return $user->canManageWaitingList();
    }

    public function create(User $user): bool
    {
        return $user->canManageWaitingList();
    }

    public function update(User $user, WaitingListEntry $waitingListEntry): bool
    {
        return $user->canManageWaitingList();
    }
}
