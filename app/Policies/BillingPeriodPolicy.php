<?php

namespace App\Policies;

use App\Models\BillingPeriod;
use App\Models\User;

class BillingPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageBilling();
    }

    public function view(User $user, BillingPeriod $period): bool
    {
        return $user->canManageBilling();
    }

    public function create(User $user): bool
    {
        return $user->canManageBilling();
    }

    public function update(User $user, BillingPeriod $period): bool
    {
        return $user->canManageBilling() && $period->isEditable();
    }

    public function calculate(User $user, BillingPeriod $period): bool
    {
        return $user->canManageBilling() && $period->canBeCalculated();
    }

    public function approve(User $user, BillingPeriod $period): bool
    {
        return $user->canManageBilling();
    }

    public function archive(User $user, BillingPeriod $period): bool
    {
        return $user->canManageBilling();
    }
}
