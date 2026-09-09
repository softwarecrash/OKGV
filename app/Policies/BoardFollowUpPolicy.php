<?php

namespace App\Policies;

use App\Enums\UserPermission;
use App\Models\BoardFollowUp;
use App\Models\BoardMeeting;
use App\Models\User;

class BoardFollowUpPolicy
{
    public function complete(User $user, BoardFollowUp $task): bool
    {
        return $user->can('viewAny', BoardMeeting::class) && $task->completed_at === null
            && ($user->hasPermission(UserPermission::ManageBoardWork) || $task->assigned_to === $user->id);
    }
}
