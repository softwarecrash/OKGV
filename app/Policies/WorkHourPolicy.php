<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkHour;

class WorkHourPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageBilling();
    }

    public function view(User $user, WorkHour $workHour): bool
    {
        return $user->canManageBilling();
    }

    public function create(User $user): bool
    {
        return $user->canManageBilling();
    }

    public function update(User $user, WorkHour $workHour): bool
    {
        return $user->canManageBilling() && $workHour->billingPeriod->isEditable();
    }
}
