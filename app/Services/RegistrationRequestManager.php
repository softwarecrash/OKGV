<?php

namespace App\Services;

use App\Enums\RegistrationRequestStatus;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegistrationRequestManager
{
    public function approve(
        RegistrationRequest $registrationRequest,
        Member $member,
        User $actor,
        ?string $reviewNote = null,
        string $memberEmailAction = 'keep',
    ): User {
        $user = DB::transaction(function () use (
            $registrationRequest,
            $member,
            $actor,
            $reviewNote,
            $memberEmailAction,
        ): User {
            $registrationRequest = RegistrationRequest::query()
                ->lockForUpdate()
                ->findOrFail($registrationRequest->id);
            $member = Member::query()->lockForUpdate()->findOrFail($member->id);

            if ($registrationRequest->status !== RegistrationRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Diese Registrierungsanfrage wurde bereits bearbeitet.',
                ]);
            }

            if ($member->user_id !== null) {
                throw ValidationException::withMessages([
                    'member_id' => 'Dieses Mitglied besitzt bereits ein Benutzerkonto.',
                ]);
            }

            $hasActiveTenancy = $member->parcelTenancies()
                ->activeOn()
                ->where('parcel_id', $registrationRequest->parcel_id)
                ->exists();

            if (! $hasActiveTenancy) {
                throw ValidationException::withMessages([
                    'member_id' => 'Das Mitglied ist der angegebenen Parzelle aktuell nicht als Pächter zugeordnet.',
                ]);
            }

            if (User::query()->where('email', $registrationRequest->email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Für diese E-Mail-Adresse existiert bereits ein Benutzerkonto.',
                ]);
            }

            $user = User::create([
                'name' => $member->full_name,
                'email' => $registrationRequest->email,
                'password' => $registrationRequest->password,
                'role' => UserRole::Tenant,
            ]);

            $previousMemberEmail = $member->email;
            $member->update([
                'user_id' => $user->id,
                'email' => $memberEmailAction === 'use_registration'
                    ? $registrationRequest->email
                    : $member->email,
            ]);
            $registrationRequest->update([
                'status' => RegistrationRequestStatus::Approved,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
                'password' => null,
            ]);

            AuditLogger::log('tenant.registration.approved', $actor, $registrationRequest, [
                'member_id' => $member->id,
                'user_id' => $user->id,
                'member_email_action' => $memberEmailAction,
                'member_email_changed' => $previousMemberEmail !== $member->email,
            ]);

            return $user;
        });

        $user->sendEmailVerificationNotification();

        return $user;
    }

    public function reject(
        RegistrationRequest $registrationRequest,
        User $actor,
        string $reviewNote,
    ): RegistrationRequest {
        return DB::transaction(function () use ($registrationRequest, $actor, $reviewNote): RegistrationRequest {
            $registrationRequest = RegistrationRequest::query()
                ->lockForUpdate()
                ->findOrFail($registrationRequest->id);

            if ($registrationRequest->status !== RegistrationRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Diese Registrierungsanfrage wurde bereits bearbeitet.',
                ]);
            }

            $registrationRequest->update([
                'status' => RegistrationRequestStatus::Rejected,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
                'password' => null,
            ]);

            AuditLogger::log('tenant.registration.rejected', $actor, $registrationRequest);

            return $registrationRequest;
        });
    }
}
