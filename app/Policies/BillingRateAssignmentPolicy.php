<?php

namespace App\Policies;

use App\Models\BillingRateAssignment;
use App\Models\User;

class BillingRateAssignmentPolicy
{
    public function create(User $user): bool
    {
        return $user->canManageBilling();
    }

    public function delete(User $user, BillingRateAssignment $assignment): bool
    {
        return $user->canManageBilling()
            && $assignment->billingRate->billingPeriod->isEditable();
    }
}
