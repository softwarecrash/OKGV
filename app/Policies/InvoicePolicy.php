<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canManageBilling() || $user->member()->exists();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->role->canManageBilling()) {
            return true;
        }

        return $invoice->status === InvoiceStatus::Approved
            && $invoice->member->user_id === $user->id;
    }
}
