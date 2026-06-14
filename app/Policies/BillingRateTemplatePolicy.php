<?php

namespace App\Policies;

use App\Models\BillingRateTemplate;
use App\Models\User;

class BillingRateTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageBilling();
    }

    public function create(User $user): bool
    {
        return $user->canManageBillingTemplates();
    }

    public function update(User $user, BillingRateTemplate $template): bool
    {
        return $user->canManageBillingTemplates();
    }
}
