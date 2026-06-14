<?php

namespace App\Policies;

use App\Models\BillingRate;
use App\Models\User;

class BillingRatePolicy
{
    public function create(User $user): bool
    {
        return $user->canManageBilling();
    }

    public function update(User $user, BillingRate $rate): bool
    {
        return $user->canManageBilling() && $rate->billingPeriod->isEditable();
    }

    public function delete(User $user, BillingRate $rate): bool
    {
        return $this->update($user, $rate);
    }
}
