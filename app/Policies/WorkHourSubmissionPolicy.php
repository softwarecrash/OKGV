<?php

namespace App\Policies;

use App\Enums\WorkHourSubmissionStatus;
use App\Models\User;
use App\Models\WorkHourSubmission;

class WorkHourSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasTenantAccess()
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
        return $user->hasTenantAccess();
    }

    public function review(User $user, WorkHourSubmission $submission): bool
    {
        return $user->canManageWorkEvents()
            && $submission->status === WorkHourSubmissionStatus::Pending;
    }

    public function acknowledge(User $user, WorkHourSubmission $submission): bool
    {
        return $submission->submitted_by === $user->id
            && $submission->status === WorkHourSubmissionStatus::Rejected
            && $submission->tenant_acknowledged_at === null;
    }

    public function downloadPhoto(User $user, WorkHourSubmission $submission): bool
    {
        return $this->view($user, $submission) && $submission->photo_path !== null;
    }
}
