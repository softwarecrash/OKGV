<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkEventParticipant;

class WorkEventParticipantPolicy
{
    public function create(User $user): bool
    {
        return $user->canManageWorkEvents();
    }

    public function update(User $user, WorkEventParticipant $participant): bool
    {
        return $user->canManageWorkEvents()
            && $participant->workEvent->billingPeriod->isEditable();
    }
}
