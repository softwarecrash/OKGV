<?php

namespace App\Policies;

use App\Enums\UserPermission;
use App\Models\BoardMeeting;
use App\Models\User;

class BoardMeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && ! $user->hasPendingRegistrationApproval()
            && ($user->hasPermission(UserPermission::ViewBoardWork) || $user->hasPermission(UserPermission::ManageBoardWork));
    }

    public function view(User $user, BoardMeeting $meeting): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission(UserPermission::ManageBoardWork);
    }

    public function update(User $user, BoardMeeting $meeting): bool
    {
        return $this->create($user) && $meeting->finalized_at === null && $meeting->archived_at === null;
    }

    public function finalize(User $user, BoardMeeting $meeting): bool
    {
        return $this->update($user, $meeting);
    }

    public function archive(User $user, BoardMeeting $meeting): bool
    {
        return $this->create($user) && $meeting->archived_at === null;
    }

    public function unarchive(User $user, BoardMeeting $meeting): bool
    {
        return $this->create($user) && $meeting->archived_at !== null;
    }
}
