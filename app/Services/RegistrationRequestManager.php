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
        ?Member $member,
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
            $member = $member === null
                ? null
                : Member::query()->lockForUpdate()->findOrFail($member->id);

            if ($registrationRequest->status !== RegistrationRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Diese Registrierungsanfrage wurde bereits bearbeitet.',
                ]);
            }

            if ($member !== null && $member->user_id !== null) {
                throw ValidationException::withMessages([
                    'member_id' => 'Dieses Mitglied besitzt bereits ein Benutzerkonto.',
                ]);
            }

            if ($registrationRequest->parcel_id !== null) {
                if ($member === null) {
                    throw ValidationException::withMessages([
                        'member_id' => 'Für eine Pächterregistrierung muss ein Mitglied der angegebenen Parzelle ausgewählt werden.',
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
            }

            $user = $registrationRequest->resolvedUser();

            if (User::query()
                ->where('email', $registrationRequest->email)
                ->when($user !== null, fn ($query) => $query->whereKeyNot($user->id))
                ->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Für diese E-Mail-Adresse existiert bereits ein Benutzerkonto.',
                ]);
            }

            if ($user === null) {
                $user = User::create([
                    'name' => $member?->full_name ?? $registrationRequest->full_name,
                    'email' => $registrationRequest->email,
                    'password' => $registrationRequest->password,
                    'role' => UserRole::Tenant,
                ]);
            } else {
                $user->update([
                    'name' => $member?->full_name ?? $registrationRequest->full_name,
                ]);
            }

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            if ($registrationRequest->user_id !== $user->id) {
                $registrationRequest->user()->associate($user);
            }

            $previousMemberEmail = $member?->email;

            if ($member !== null) {
                $member->update([
                    'user_id' => $user->id,
                    'email' => $memberEmailAction === 'use_registration'
                        ? $registrationRequest->email
                        : $member->email,
                ]);
            }

            $registrationRequest->update([
                'status' => RegistrationRequestStatus::Approved,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
                'password' => null,
            ]);

            AuditLogger::log('tenant.registration.approved', $actor, $registrationRequest, [
                'member_id' => $member?->id,
                'user_id' => $user->id,
                'member_email_action' => $memberEmailAction,
                'member_email_changed' => $member !== null && $previousMemberEmail !== $member->email,
            ]);

            return $user;
        });

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

            $user = $registrationRequest->resolvedUser();
            $registrationRequest->update([
                'status' => RegistrationRequestStatus::Rejected,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
                'password' => null,
            ]);

            if ($user !== null && $user->member === null && $user->role === UserRole::Tenant) {
                $user->delete();
            }

            AuditLogger::log('tenant.registration.rejected', $actor, $registrationRequest);

            return $registrationRequest;
        });
    }

    public function linkMember(
        RegistrationRequest $registrationRequest,
        Member $member,
        User $actor,
        ?string $reviewNote = null,
        string $memberEmailAction = 'keep',
    ): RegistrationRequest {
        return DB::transaction(function () use (
            $registrationRequest,
            $member,
            $actor,
            $reviewNote,
            $memberEmailAction,
        ): RegistrationRequest {
            $registrationRequest = RegistrationRequest::query()
                ->lockForUpdate()
                ->findOrFail($registrationRequest->id);
            $member = Member::query()->lockForUpdate()->findOrFail($member->id);

            if ($registrationRequest->status !== RegistrationRequestStatus::Approved) {
                throw ValidationException::withMessages([
                    'status' => 'Nur bereits freigegebene Registrierungsanfragen können nachträglich verknüpft werden.',
                ]);
            }

            $user = $registrationRequest->resolvedUser();

            if ($user === null) {
                throw ValidationException::withMessages([
                    'user_id' => 'Für diese Anfrage wurde kein Benutzerkonto gefunden.',
                ]);
            }

            if ($user->member()->exists()) {
                throw ValidationException::withMessages([
                    'member_id' => 'Dieses Benutzerkonto ist bereits mit einem Mitglied verknüpft.',
                ]);
            }

            if ($member->user_id !== null) {
                throw ValidationException::withMessages([
                    'member_id' => 'Dieses Mitglied besitzt bereits ein Benutzerkonto.',
                ]);
            }

            if ($registrationRequest->parcel_id !== null) {
                $hasActiveTenancy = $member->parcelTenancies()
                    ->activeOn()
                    ->where('parcel_id', $registrationRequest->parcel_id)
                    ->exists();

                if (! $hasActiveTenancy) {
                    throw ValidationException::withMessages([
                        'member_id' => 'Das Mitglied ist der angegebenen Parzelle aktuell nicht als Pächter zugeordnet.',
                    ]);
                }
            }

            $previousMemberEmail = $member->email;

            $member->update([
                'user_id' => $user->id,
                'email' => $memberEmailAction === 'use_registration'
                    ? $registrationRequest->email
                    : $member->email,
            ]);

            if ($registrationRequest->user_id !== $user->id) {
                $registrationRequest->user()->associate($user);
            }

            $registrationRequest->update([
                'review_note' => $reviewNote ?: $registrationRequest->review_note,
            ]);

            AuditLogger::log('tenant.registration.member_linked', $actor, $registrationRequest, [
                'member_id' => $member->id,
                'user_id' => $user->id,
                'member_email_action' => $memberEmailAction,
                'member_email_changed' => $previousMemberEmail !== $member->email,
            ]);

            return $registrationRequest;
        });
    }
}
