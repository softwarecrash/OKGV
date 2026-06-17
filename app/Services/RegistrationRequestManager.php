<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Enums\NumberSequenceType;
use App\Enums\RegistrationRequestStatus;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegistrationRequestManager
{
    public function __construct(
        private readonly NumberSequenceManager $numberSequenceManager,
        private readonly ParcelTenancyManager $parcelTenancyManager,
    ) {}

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

            if ($registrationRequest->parcel_id !== null && $member !== null) {
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

            $createdMember = false;
            $previousMemberEmail = $member?->email;

            if ($member === null) {
                $member = $this->createMemberFromRegistration($registrationRequest, $user);
                $createdMember = true;
                $previousMemberEmail = null;
            }

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
                'member_created' => $createdMember,
                'member_email_action' => $memberEmailAction,
                'member_email_changed' => $member !== null && $previousMemberEmail !== $member->email,
            ]);

            return $user;
        });

        return $user;
    }

    private function createMemberFromRegistration(RegistrationRequest $registrationRequest, User $user): Member
    {
        $member = Member::create([
            'user_id' => $user->id,
            'member_number' => $this->numberSequenceManager->next(NumberSequenceType::Member),
            'first_name' => $registrationRequest->first_name,
            'last_name' => $registrationRequest->last_name,
            'street' => 'Noch nicht erfasst',
            'zip' => '00000',
            'city' => 'Noch nicht erfasst',
            'email' => $registrationRequest->email,
            'joined_at' => now()->toDateString(),
            'status' => MemberStatus::Active,
            'notes' => 'Automatisch aus einer freigegebenen Registrierungsanfrage angelegt. Adresse bitte in den Stammdaten ergänzen.',
        ]);

        if ($registrationRequest->parcel_id !== null) {
            $primaryExists = $registrationRequest->parcel
                ?->tenancies()
                ->activeOn()
                ->where('is_primary', true)
                ->exists() ?? false;

            $this->parcelTenancyManager->save([
                'parcel_id' => $registrationRequest->parcel_id,
                'member_id' => $member->id,
                'starts_at' => now()->toDateString(),
                'is_primary' => ! $primaryExists,
                'notes' => 'Automatisch bei Freigabe der Registrierungsanfrage eingetragen.',
            ]);
        }

        return $member;
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

    public function linkAccount(
        RegistrationRequest $registrationRequest,
        User $actor,
    ): RegistrationRequest {
        return DB::transaction(function () use ($registrationRequest, $actor): RegistrationRequest {
            $registrationRequest = RegistrationRequest::query()
                ->lockForUpdate()
                ->findOrFail($registrationRequest->id);

            if ($registrationRequest->status !== RegistrationRequestStatus::Approved) {
                throw ValidationException::withMessages([
                    'status' => 'Nur bereits freigegebene Registrierungsanfragen können nachträglich mit einem Konto verknüpft werden.',
                ]);
            }

            $user = $registrationRequest->resolvedUser();

            if ($user === null) {
                throw ValidationException::withMessages([
                    'user_id' => 'Für diese Anfrage wurde kein Benutzerkonto gefunden.',
                ]);
            }

            if ($registrationRequest->user_id !== null && $registrationRequest->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'user_id' => 'Diese Anfrage ist bereits mit einem anderen Benutzerkonto verknüpft.',
                ]);
            }

            $registrationRequest->user()->associate($user);
            $registrationRequest->save();

            AuditLogger::log('tenant.registration.account_linked', $actor, $registrationRequest, [
                'user_id' => $user->id,
            ]);

            return $registrationRequest;
        });
    }

    public function createMemberForApprovedRequest(
        RegistrationRequest $registrationRequest,
        User $actor,
    ): RegistrationRequest {
        return DB::transaction(function () use ($registrationRequest, $actor): RegistrationRequest {
            $registrationRequest = RegistrationRequest::query()
                ->lockForUpdate()
                ->findOrFail($registrationRequest->id);

            if ($registrationRequest->status !== RegistrationRequestStatus::Approved) {
                throw ValidationException::withMessages([
                    'status' => 'Nur bereits freigegebene Registrierungsanfragen können nachträglich in ein Mitglied übernommen werden.',
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

            if ($registrationRequest->user_id !== $user->id) {
                $registrationRequest->user()->associate($user);
                $registrationRequest->save();
            }

            $member = $this->createMemberFromRegistration($registrationRequest, $user);

            AuditLogger::log('tenant.registration.member_created', $actor, $registrationRequest, [
                'member_id' => $member->id,
                'user_id' => $user->id,
                'parcel_id' => $registrationRequest->parcel_id,
            ]);

            return $registrationRequest;
        });
    }
}
