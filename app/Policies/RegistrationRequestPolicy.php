<?php

namespace App\Policies;

use App\Enums\RegistrationRequestStatus;
use App\Models\RegistrationRequest;
use App\Models\User;

class RegistrationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canReviewTenantRegistrations();
    }

    public function view(User $user, RegistrationRequest $registrationRequest): bool
    {
        return $user->role->canReviewTenantRegistrations();
    }

    public function review(User $user, RegistrationRequest $registrationRequest): bool
    {
        return $user->role->canReviewTenantRegistrations()
            && $registrationRequest->status === RegistrationRequestStatus::Pending;
    }
}
