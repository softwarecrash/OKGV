<?php

namespace App\Policies;

use App\Enums\MeterReadingSubmissionStatus;
use App\Enums\UserRole;
use App\Models\MeterReadingSubmission;
use App\Models\User;

class MeterReadingSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Tenant
            ? $user->member()->exists()
            : $user->canReviewMeterReadingSubmissions();
    }

    public function view(User $user, MeterReadingSubmission $submission): bool
    {
        return $submission->submitted_by === $user->id
            || $user->canReviewMeterReadingSubmissions();
    }

    public function review(User $user, MeterReadingSubmission $submission): bool
    {
        return $user->canReviewMeterReadingSubmissions()
            && $submission->status === MeterReadingSubmissionStatus::Pending;
    }

    public function viewPhoto(User $user, MeterReadingSubmission $submission): bool
    {
        return $this->view($user, $submission) && $submission->photo_path !== null;
    }
}
