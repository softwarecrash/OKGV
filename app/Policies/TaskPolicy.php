<?php

namespace App\Policies;

use App\Enums\FeatureModule;
use App\Enums\UserPermission;
use App\Models\BoardMeeting;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return FeatureModule::Tasks->enabled() && $user->hasVerifiedEmail() && ! $user->hasPendingRegistrationApproval()
            && ($user->hasPermission(UserPermission::ViewTasks) || $user->hasPermission(UserPermission::ManageTasks) || $user->can('viewAny', BoardMeeting::class));
    }

    public function view(User $user, Task $task): bool
    {
        return $this->viewAny($user) && ($task->board_resolution_id !== null
            ? $user->can('viewAny', BoardMeeting::class)
            : ($user->hasPermission(UserPermission::ManageTasks) || ($user->hasPermission(UserPermission::ViewTasks) && $task->assigned_to === $user->id)));
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission(UserPermission::ManageTasks);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->view($user, $task) && $task->isOpen() && $task->archived_at === null
            && ($user->hasPermission(UserPermission::ManageTasks) || ($task->board_resolution_id !== null && $user->hasPermission(UserPermission::ManageBoardWork)));
    }

    public function complete(User $user, Task $task): bool
    {
        return $this->view($user, $task) && $task->isOpen() && $task->archived_at === null
            && ($this->update($user, $task) || $task->assigned_to === $user->id);
    }

    public function start(User $user, Task $task): bool
    {
        return $this->complete($user, $task) && $task->started_at === null;
    }

    public function cancel(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function archive(User $user, Task $task): bool
    {
        return $this->view($user, $task) && ! $task->isOpen() && $task->archived_at === null && $this->manages($user, $task);
    }

    public function restore(User $user, Task $task): bool
    {
        return $this->view($user, $task) && $task->archived_at !== null && $this->manages($user, $task);
    }

    private function manages(User $user, Task $task): bool
    {
        return $user->hasPermission(UserPermission::ManageTasks) || ($task->board_resolution_id !== null && $user->hasPermission(UserPermission::ManageBoardWork));
    }
}
