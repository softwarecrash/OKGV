<?php

namespace App\Policies;

use App\Enums\DunningNoticeStatus;
use App\Models\DunningNotice;
use App\Models\User;

class DunningNoticePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageBilling();
    }

    public function view(User $user, DunningNotice $notice): bool
    {
        return $user->canManageBilling()
            || $user->can('view', $notice->invoice);
    }

    public function create(User $user): bool
    {
        return $user->canManageBilling();
    }

    public function cancel(User $user, DunningNotice $notice): bool
    {
        return $user->canManageBilling()
            && $notice->status === DunningNoticeStatus::Issued;
    }
}
