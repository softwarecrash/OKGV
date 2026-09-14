<?php

namespace App\Policies;

use App\Enums\PrivacyErasureStatus;
use App\Models\PrivacyErasureRequest;
use App\Models\User;

class PrivacyErasureRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManagePrivacy();
    }

    public function view(User $user, PrivacyErasureRequest $request): bool
    {
        return $user->canManagePrivacy()
            || $request->member->user_id === $user->id;
    }

    public function review(User $user, PrivacyErasureRequest $request): bool
    {
        return $user->canManagePrivacy();
    }

    public function anonymize(User $user, PrivacyErasureRequest $request): bool
    {
        return $user->isAdministrator();
    }

    public function cancel(User $user, PrivacyErasureRequest $request): bool
    {
        return $user->canManagePrivacy() && in_array($request->status, [
            PrivacyErasureStatus::Pending,
            PrivacyErasureStatus::Blocked,
            PrivacyErasureStatus::Ready,
        ], true);
    }
}
