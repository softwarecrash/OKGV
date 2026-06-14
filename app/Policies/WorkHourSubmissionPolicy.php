<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\WorkHourSubmissionStatus;
use App\Models\User;
use App\Models\WorkHourSubmission;

class WorkHourSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Tenant
            ? $user->member()->exists()
            : $user->canManageWorkEvents();
    }

    public function view(User $user, WorkHourSubmission $submission): bool
    {
        return $submission->submitted_by === $user->id
            || $user->canManageWorkEvents();
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Tenant && $user->member()->exists();
    }

    public function review(User $user, WorkHourSubmission $submission): bool
    {
        return $user->canManageWorkEvents()
            && $submission->status === WorkHourSubmissionStatus::Pending;
    }

    public function downloadPhoto(User $user, WorkHourSubmission $submission): bool
    {
        return $this->view($user, $submission) && $submission->photo_path !== null;
    }
}
