<?php

namespace App\Policies;

use App\Enums\RegistrationRequestStatus;
use App\Models\Member;
use App\Models\RegistrationRequest;
use App\Models\User;

class RegistrationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canReviewTenantRegistrations();
    }

    public function view(User $user, RegistrationRequest $registrationRequest): bool
    {
        return $user->canReviewTenantRegistrations();
    }

    public function review(User $user, RegistrationRequest $registrationRequest): bool
    {
        return $user->canReviewTenantRegistrations()
            && $registrationRequest->status === RegistrationRequestStatus::Pending;
    }

    public function linkMember(User $user, RegistrationRequest $registrationRequest): bool
    {
        return $user->canReviewTenantRegistrations()
            && $registrationRequest->status === RegistrationRequestStatus::Approved
            && $registrationRequest->parcel_id !== null
            && $registrationRequest->resolvedUser() !== null
            && $registrationRequest->resolvedUser()?->member()->doesntExist();
    }

    public function linkAccount(User $user, RegistrationRequest $registrationRequest): bool
    {
        return $user->canReviewTenantRegistrations()
            && $registrationRequest->status === RegistrationRequestStatus::Approved
            && $registrationRequest->parcel_id === null
            && $registrationRequest->user_id === null
            && $registrationRequest->resolvedUser() !== null;
    }

    public function createMember(User $user, RegistrationRequest $registrationRequest): bool
    {
        $hasLinkableMemberForParcel = $registrationRequest->parcel_id !== null
            && Member::query()
                ->whereNull('user_id')
                ->whereHas('parcelTenancies', fn ($query) => $query
                    ->activeOn()
                    ->where('parcel_id', $registrationRequest->parcel_id))
                ->exists();

        return $user->canReviewTenantRegistrations()
            && $registrationRequest->status === RegistrationRequestStatus::Approved
            && $registrationRequest->resolvedUser() !== null
            && $registrationRequest->resolvedUser()?->member()->doesntExist()
            && ! $hasLinkableMemberForParcel;
    }
}
