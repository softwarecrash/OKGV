<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkEvent;

class WorkEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageWorkEvents();
    }

    public function view(User $user, WorkEvent $workEvent): bool
    {
        return $user->canManageWorkEvents();
    }

    public function create(User $user): bool
    {
        return $user->canManageWorkEvents();
    }

    public function update(User $user, WorkEvent $workEvent): bool
    {
        return $user->canManageWorkEvents() && $workEvent->billingPeriod->isEditable();
    }
}
