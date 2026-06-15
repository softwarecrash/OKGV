<?php

namespace App\Services;

use App\Enums\InvoicePaymentStatus;
use App\Enums\MemberStatus;
use App\Enums\PrivacyErasureStatus;
use App\Enums\SepaMandateStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\PrivacyErasureRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;

final class PrivacyErasureService
{
    /**
     * @return list<string>
     */
    public function blockers(Member $member): array
    {
        $cutoff = today()->subYears((int) config('privacy.retention_years'));
        $blockers = [];

        if ($member->status !== MemberStatus::Archived || $member->archived_at === null) {
            $blockers[] = 'Das Mitglied ist noch nicht archiviert.';
        }

        if ($member->left_at === null) {
            $blockers[] = 'Es ist kein Austrittsdatum hinterlegt.';
        } elseif ($member->left_at->isAfter($cutoff)) {
            $blockers[] = sprintf(
                'Die konfigurierte Aufbewahrungsfrist endet frühestens am %s.',
                $member->left_at->addYears((int) config('privacy.retention_years'))->format('d.m.Y'),
            );
        }

        if ($member->parcelTenancies()
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>', $cutoff))
            ->exists()) {
            $blockers[] = 'Mindestens eine Pächterzuordnung ist aktiv oder liegt innerhalb der Aufbewahrungsfrist.';
        }

        if ($member->invoices()
            ->where(fn ($query) => $query
                ->whereDate('issued_at', '>', $cutoff)
                ->orWhere('payment_status', '!=', InvoicePaymentStatus::Paid->value))
            ->exists()) {
            $blockers[] = 'Rechnungen sind noch offen oder liegen innerhalb der Aufbewahrungsfrist.';
        }

        if ($member->sepaMandates()->where('status', SepaMandateStatus::Active->value)->exists()) {
            $blockers[] = 'Es besteht noch ein aktives SEPA-Mandat.';
        }

        if ($member->documents()->exists()) {
            $blockers[] = 'Mitgliedsbezogene Dokumente müssen vor einer Pseudonymisierung rechtlich geprüft werden.';
        }

        if ($member->inventoryLoans()->whereNull('returned_at')->exists()) {
            $blockers[] = 'Mindestens eine Inventarausgabe ist noch nicht zurückgegeben.';
        }

        if ($member->user !== null && $member->user->role !== UserRole::Tenant) {
            $blockers[] = 'Das verknüpfte Konto besitzt eine interne Vereinsrolle und muss zuerst getrennt geprüft werden.';
        }

        return $blockers;
    }

    public function review(PrivacyErasureRequest $request, User $reviewer, ?string $note): void
    {
        $blockers = $this->blockers($request->member);
        $request->update([
            'status' => $blockers === []
                ? PrivacyErasureStatus::Ready
                : PrivacyErasureStatus::Blocked,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
            'blockers' => $blockers ?: null,
        ]);
    }

    public function anonymize(PrivacyErasureRequest $request, User $actor): void
    {
        DB::transaction(function () use ($request, $actor): void {
            $request->refresh();

            if ($request->status !== PrivacyErasureStatus::Ready) {
                throw new LogicException('Die Löschanfrage muss unmittelbar vor der Pseudonymisierung erfolgreich geprüft werden.');
            }

            $member = $request->member()->with(['user', 'sepaMandates'])->lockForUpdate()->firstOrFail();
            $blockers = $this->blockers($member);

            if ($blockers !== []) {
                throw new LogicException(implode(' ', $blockers));
            }

            $oldUserId = $member->user_id;
            $oldEmail = $member->email;
            $anonymousReference = "ANON-{$member->id}-".Str::upper(Str::random(8));

            DB::table('invoice_recipients')
                ->where('member_id', $member->id)
                ->update([
                    'member_id' => null,
                    'member_number' => $anonymousReference,
                    'first_name' => 'Anonymisiert',
                    'last_name' => "Mitglied {$member->id}",
                    'street' => '-',
                    'zip' => '-',
                    'city' => '-',
                    'updated_at' => now(),
                ]);

            DB::table('mail_campaign_recipients')
                ->where('member_id', $member->id)
                ->update([
                    'member_id' => null,
                    'name' => 'Anonymisierte Person',
                    'email' => "anonymized-member-{$member->id}@invalid.local",
                    'error_message' => null,
                    'updated_at' => now(),
                ]);

            DB::table('letters')
                ->where('member_id', $member->id)
                ->update([
                    'member_id' => null,
                    'recipient_name' => 'Anonymisierte Person',
                    'street' => '-',
                    'zip' => '-',
                    'city' => '-',
                    'body' => 'Personenbezogener Inhalt wurde nach abgeschlossener Löschprüfung entfernt.',
                    'updated_at' => now(),
                ]);

            DB::table('parcel_tenants')
                ->where('member_id', $member->id)
                ->update(['notes' => null, 'updated_at' => now()]);

            DB::table('work_event_participants')
                ->where('member_id', $member->id)
                ->update(['notes' => null, 'updated_at' => now()]);

            DB::table('inventory_loans')
                ->where('member_id', $member->id)
                ->update([
                    'borrower_name' => 'Anonymisierte Person',
                    'condition_on_issue' => null,
                    'condition_on_return' => null,
                    'notes' => null,
                    'updated_at' => now(),
                ]);

            if ($oldEmail !== null) {
                DB::table('registration_requests')
                    ->where('email', $oldEmail)
                    ->update([
                        'first_name' => 'Anonymisiert',
                        'last_name' => "Anfrage {$member->id}",
                        'email' => "anonymized-registration-{$member->id}@invalid.local",
                        'password' => null,
                        'review_note' => null,
                        'updated_at' => now(),
                    ]);
            }

            foreach ($member->sepaMandates as $mandate) {
                $mandate->update([
                    'iban' => 'ANONYMIZED',
                    'iban_last_four' => '----',
                    'bic' => null,
                    'account_holder' => 'Anonymisiert',
                    'status' => SepaMandateStatus::Expired,
                ]);
            }

            $member->privacySetting()->updateOrCreate(
                ['member_id' => $member->id],
                [
                    'share_name' => false,
                    'share_email' => false,
                    'share_phone' => false,
                    'share_mobile' => false,
                    'share_address' => false,
                    'consented_at' => null,
                ],
            );

            $member->update([
                'user_id' => null,
                'member_number' => $anonymousReference,
                'first_name' => 'Anonymisiert',
                'last_name' => "Mitglied {$member->id}",
                'street' => '-',
                'zip' => '-',
                'city' => '-',
                'phone' => null,
                'mobile' => null,
                'email' => null,
                'notes' => null,
            ]);

            if ($oldUserId !== null) {
                $photoPaths = DB::table('meter_reading_submissions')
                    ->where('submitted_by', $oldUserId)
                    ->whereNotNull('photo_path')
                    ->pluck('photo_path')
                    ->merge(
                        DB::table('work_hour_submissions')
                            ->where('submitted_by', $oldUserId)
                            ->whereNotNull('photo_path')
                            ->pluck('photo_path'),
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                DB::table('meter_reading_submissions')
                    ->where('submitted_by', $oldUserId)
                    ->update([
                        'photo_path' => null,
                        'photo_original_name' => null,
                        'photo_mime' => null,
                        'photo_size' => null,
                        'notes' => null,
                        'review_note' => null,
                        'updated_at' => now(),
                    ]);

                DB::table('work_hour_submissions')
                    ->where('submitted_by', $oldUserId)
                    ->update([
                        'description' => 'Nachweis nach abgeschlossener Löschprüfung entfernt.',
                        'photo_path' => null,
                        'photo_original_name' => null,
                        'photo_mime' => null,
                        'photo_size' => null,
                        'review_note' => null,
                        'updated_at' => now(),
                    ]);

                DB::afterCommit(static function () use ($photoPaths): void {
                    Storage::disk('local')->delete($photoPaths);
                });

                User::query()->whereKey($oldUserId)->update([
                    'name' => 'Anonymisiertes Konto',
                    'email' => "anonymized-user-{$oldUserId}-".Str::lower(Str::random(8)).'@invalid.local',
                    'password' => bcrypt(Str::random(64)),
                    'permissions' => json_encode([]),
                    'permission_profile_id' => null,
                    'remember_token' => null,
                    'email_verified_at' => null,
                ]);

                AuditLog::query()->where('user_id', $oldUserId)->update([
                    'ip_address' => null,
                    'user_agent' => null,
                    'metadata' => null,
                ]);
            }

            AuditLog::query()
                ->where('subject_type', $member->getMorphClass())
                ->where('subject_id', $member->id)
                ->update([
                    'ip_address' => null,
                    'user_agent' => null,
                    'metadata' => null,
                ]);

            $request->update([
                'status' => PrivacyErasureStatus::Completed,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'blockers' => null,
                'completed_at' => now(),
            ]);
        });
    }
}
