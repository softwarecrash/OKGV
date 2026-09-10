<?php

namespace App\Policies;

use App\Enums\FeatureModule;
use App\Enums\TaskRecurrence;
use App\Enums\UserPermission;
use App\Models\BoardFollowUp;
use App\Models\BoardMeeting;
use App\Models\User;

class BoardFollowUpPolicy
{
    public function complete(User $user, BoardFollowUp $task): bool
    {
        if ($task->board_resolution_id === null) {
            return false;
        }
        if (FeatureModule::Tasks->enabled()) {
            return (new TaskPolicy)->complete($user, $task);
        }

        return $user->can('viewAny', BoardMeeting::class) && $task->isOpen() && $task->archived_at === null && $task->recurrence === TaskRecurrence::None
            && ($user->hasPermission(UserPermission::ManageBoardWork) || $task->assigned_to === $user->id);
    }
}
