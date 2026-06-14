<?php

namespace App\Policies;

use App\Models\BillingRate;
use App\Models\User;

class BillingRatePolicy
{
    public function create(User $user): bool
    {
        return $user->role->canManageBilling();
    }

    public function update(User $user, BillingRate $rate): bool
    {
        return $user->role->canManageBilling() && $rate->billingPeriod->isEditable();
    }

    public function delete(User $user, BillingRate $rate): bool
    {
        return $this->update($user, $rate);
    }
}
