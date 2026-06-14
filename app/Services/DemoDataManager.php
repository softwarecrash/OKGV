<?php

namespace App\Services;

use App\Enums\BillingPeriodStatus;
use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\MemberStatus;
use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingSubmissionStatus;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\ParcelStatus;
use App\Enums\UserRole;
use App\Enums\WorkEventParticipantStatus;
use App\Enums\WorkEventStatus;
use App\Enums\WorkHourSubmissionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class DemoDataManager
{
    public const EMAIL_SUFFIX = '.demo@okgv.test';

    public const MEMBER_PREFIX = 'DEMO-M-';

    public const PARCEL_PREFIX = 'DEMO-';

    public const PERIOD_PREFIX = 'DEMO Abrechnung ';

    public const METER_PREFIX = 'DEMO-';

    /**
     * @return array<string, int>
     */
    public function seed(?string $password = null): array
    {
        $password ??= config('demo.password');

        if (! is_string($password) || trim($password) === '') {
            throw new RuntimeException(
                'OKGV_DEMO_PASSWORD ist nicht gesetzt. Hinterlege ein lokales Demo-Passwort in der .env.',
            );
        }

        $this->purge();

        return DB::transaction(function () use ($password): array {
            $now = now();
            $accounts = [
                ['Vorstand', 'Demo', 'vorstand', UserRole::Board],
                ['Anna', 'Apfelbaum', 'paechter1', UserRole::Tenant],
                ['Bernd', 'Bienenstock', 'paechter2', UserRole::Tenant],
                ['Clara', 'Gartenweg', 'paechter3', UserRole::Tenant],
                ['Daniel', 'Sonnenblume', 'paechter4', UserRole::Tenant],
            ];
            $members = [];
            $parcels = [];
            $users = [];

            foreach ($accounts as $index => [$firstName, $lastName, $login, $role]) {
                $email = "{$login}".self::EMAIL_SUFFIX;
                $userId = DB::table('users')->insertGetId([
                    'name' => "{$firstName} {$lastName}",
                    'email' => $email,
                    'email_verified_at' => $now,
                    'password' => Hash::make($password),
                    'role' => $role->value,
                    'can_correct_meter_readings' => $role === UserRole::Board,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $memberId = DB::table('members')->insertGetId([
                    'user_id' => $userId,
                    'member_number' => self::MEMBER_PREFIX.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'street' => 'Gartenstraße '.($index + 1),
                    'zip' => '12345',
                    'city' => 'Musterstadt',
                    'phone' => '030 55500'.($index + 1),
                    'mobile' => '0151 555000'.($index + 1),
                    'email' => $email,
                    'joined_at' => '2023-01-01',
                    'status' => MemberStatus::Active->value,
                    'notes' => 'Automatisch erzeugter Demo-Datensatz.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $parcelId = DB::table('parcels')->insertGetId([
                    'parcel_number' => self::PARCEL_PREFIX.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'area_sqm' => 280 + ($index * 35),
                    'status' => ParcelStatus::Assigned->value,
                    'location_description' => 'Demoweg '.($index + 1),
                    'notes' => 'Automatisch erzeugte Demo-Parzelle.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('parcel_tenants')->insert([
                    'parcel_id' => $parcelId,
                    'member_id' => $memberId,
                    'starts_at' => '2023-01-01',
                    'is_primary' => true,
                    'notes' => 'Demo-Pachtverhältnis.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $users[] = $userId;
                $members[] = $memberId;
                $parcels[] = $parcelId;
            }

            $periods = $this->seedPeriods($now);
            $meters = $this->seedMetersAndReadings($parcels, $now);
            $this->seedMeterReadingSubmission($meters[2], $users[1], $now);
            $this->seedBillingRates($periods, $now);
            $this->seedWorkData($periods, $parcels, $members, $users, $now);

            return [
                'users' => count($users),
                'members' => count($members),
                'parcels' => count($parcels),
                'meters' => count($meters),
                'periods' => count($periods),
            ];
        });
    }

    /**
     * @return array<string, int>
     */
    public function purge(): array
    {
        $storedFiles = [];
        $counts = DB::transaction(function () use (&$storedFiles): array {
            $userIds = DB::table('users')
                ->where('email', 'like', '%'.self::EMAIL_SUFFIX)
                ->pluck('id');
            $memberIds = DB::table('members')
                ->where('member_number', 'like', self::MEMBER_PREFIX.'%')
                ->pluck('id');
            $parcelIds = DB::table('parcels')
                ->where('parcel_number', 'like', self::PARCEL_PREFIX.'%')
                ->pluck('id');
            $periodIds = DB::table('billing_periods')
                ->where('name', 'like', self::PERIOD_PREFIX.'%')
                ->pluck('id');
            $meterIds = DB::table('meters')
                ->where('meter_number', 'like', self::METER_PREFIX.'%')
                ->pluck('id');
            $readingIds = DB::table('meter_readings')
                ->whereIn('meter_id', $meterIds)
                ->pluck('id');
            $eventIds = DB::table('work_events')
                ->whereIn('billing_period_id', $periodIds)
                ->where('title', 'like', 'DEMO %')
                ->pluck('id');
            $rateIds = DB::table('billing_rates')
                ->whereIn('billing_period_id', $periodIds)
                ->pluck('id');
            $invoiceIds = DB::table('invoices')
                ->whereIn('billing_period_id', $periodIds)
                ->orWhereIn('member_id', $memberIds)
                ->pluck('id');
            $mandateIds = DB::table('sepa_mandates')
                ->whereIn('member_id', $memberIds)
                ->pluck('id');
            $batchIds = DB::table('payment_batches')
                ->whereIn('created_by', $userIds)
                ->orWhereIn('id', DB::table('payment_batch_items')
                    ->select('payment_batch_id')
                    ->whereIn('invoice_id', $invoiceIds)
                    ->orWhereIn('sepa_mandate_id', $mandateIds))
                ->pluck('id');
            $campaignIds = DB::table('mail_campaigns')
                ->whereIn('created_by', $userIds)
                ->pluck('id');
            $documentIds = DB::table('documents')
                ->whereIn('member_id', $memberIds)
                ->orWhereIn('parcel_id', $parcelIds)
                ->orWhereIn('uploaded_by', $userIds)
                ->pluck('id');
            $storedFiles = DB::table('documents')
                ->whereIn('id', $documentIds)
                ->pluck('file_path')
                ->merge(
                    DB::table('document_versions')
                        ->whereIn('document_id', $documentIds)
                        ->pluck('file_path'),
                )
                ->filter()
                ->unique()
                ->values()
                ->all();

            DB::table('work_hour_submissions')
                ->whereIn('billing_period_id', $periodIds)
                ->orWhereIn('parcel_id', $parcelIds)
                ->orWhereIn('submitted_by', $userIds)
                ->delete();
            DB::table('work_event_participants')
                ->whereIn('work_event_id', $eventIds)
                ->orWhereIn('member_id', $memberIds)
                ->orWhereIn('parcel_id', $parcelIds)
                ->delete();
            DB::table('work_events')->whereIn('id', $eventIds)->delete();
            DB::table('work_hours')
                ->whereIn('billing_period_id', $periodIds)
                ->orWhereIn('parcel_id', $parcelIds)
                ->delete();
            DB::table('meter_reading_submissions')
                ->whereIn('meter_id', $meterIds)
                ->orWhereIn('submitted_by', $userIds)
                ->delete();
            DB::table('meter_reading_corrections')
                ->whereIn('meter_reading_id', $readingIds)
                ->orWhereIn('corrected_by', $userIds)
                ->delete();
            DB::table('meter_readings')->whereIn('meter_id', $meterIds)->delete();
            DB::table('meters')->whereIn('id', $meterIds)->delete();
            DB::table('dunning_notices')
                ->whereIn('invoice_id', $invoiceIds)
                ->orWhereIn('created_by', $userIds)
                ->orWhereIn('cancelled_by', $userIds)
                ->delete();
            DB::table('payment_batch_items')
                ->whereIn('payment_batch_id', $batchIds)
                ->orWhereIn('invoice_id', $invoiceIds)
                ->orWhereIn('sepa_mandate_id', $mandateIds)
                ->delete();
            DB::table('payment_batches')->whereIn('id', $batchIds)->delete();
            DB::table('invoice_recipients')->whereIn('invoice_id', $invoiceIds)->delete();
            DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->delete();
            DB::table('invoices')->whereIn('id', $invoiceIds)->delete();
            DB::table('sepa_mandates')->whereIn('id', $mandateIds)->delete();
            DB::table('billing_rate_assignments')
                ->whereIn('billing_rate_id', $rateIds)
                ->orWhereIn('member_id', $memberIds)
                ->orWhereIn('parcel_id', $parcelIds)
                ->delete();
            DB::table('billing_rates')->whereIn('id', $rateIds)->delete();
            DB::table('registration_requests')
                ->whereIn('parcel_id', $parcelIds)
                ->orWhereIn('reviewed_by', $userIds)
                ->delete();
            DB::table('document_versions')->whereIn('document_id', $documentIds)->delete();
            DB::table('documents')->whereIn('id', $documentIds)->delete();
            DB::table('letters')
                ->whereIn('member_id', $memberIds)
                ->orWhereIn('created_by', $userIds)
                ->delete();
            DB::table('mail_campaign_recipients')
                ->whereIn('mail_campaign_id', $campaignIds)
                ->delete();
            DB::table('mail_campaigns')->whereIn('id', $campaignIds)->delete();
            DB::table('parcel_tenants')
                ->whereIn('member_id', $memberIds)
                ->orWhereIn('parcel_id', $parcelIds)
                ->delete();
            DB::table('members')->whereIn('id', $memberIds)->delete();
            DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            DB::table('password_reset_tokens')
                ->where('email', 'like', '%'.self::EMAIL_SUFFIX)
                ->delete();
            DB::table('audit_logs')->whereIn('user_id', $userIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
            DB::table('parcels')->whereIn('id', $parcelIds)->delete();
            DB::table('billing_periods')->whereIn('id', $periodIds)->delete();

            return [
                'users' => $userIds->count(),
                'members' => $memberIds->count(),
                'parcels' => $parcelIds->count(),
                'meters' => $meterIds->count(),
                'periods' => $periodIds->count(),
            ];
        });

        foreach ($storedFiles as $storedFile) {
            Storage::disk('local')->delete($storedFile);
        }

        return $counts;
    }

    /**
     * @return array<int, int>
     */
    private function seedPeriods(mixed $now): array
    {
        $periods = [];

        foreach ([
            2024 => BillingPeriodStatus::Archived,
            2025 => BillingPeriodStatus::Approved,
            2026 => BillingPeriodStatus::Draft,
        ] as $year => $status) {
            $periods[$year] = DB::table('billing_periods')->insertGetId([
                'name' => self::PERIOD_PREFIX.$year,
                'starts_at' => "{$year}-01-01",
                'ends_at' => "{$year}-12-31",
                'due_at' => ($year + 1).'-02-15',
                'status' => $status->value,
                'calculated_at' => $status === BillingPeriodStatus::Draft ? null : "{$year}-12-31 18:00:00",
                'approved_at' => in_array($status, [BillingPeriodStatus::Approved, BillingPeriodStatus::Archived], true)
                    ? ($year + 1).'-01-15 10:00:00'
                    : null,
                'archived_at' => $status === BillingPeriodStatus::Archived
                    ? ($year + 1).'-03-01 10:00:00'
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $periods;
    }

    /**
     * @param  list<int>  $parcelIds
     * @return list<int>
     */
    private function seedMetersAndReadings(array $parcelIds, mixed $now): array
    {
        $meterIds = [];

        foreach ($parcelIds as $index => $parcelId) {
            foreach ([MeterType::Water, MeterType::Electricity] as $type) {
                $code = $type === MeterType::Water ? 'W' : 'S';
                $start = $type === MeterType::Water ? 100 + ($index * 20) : 1000 + ($index * 150);
                $increments = $type === MeterType::Water ? [0, 32, 68, 86] : [0, 210, 455, 590];
                $meterId = DB::table('meters')->insertGetId([
                    'parcel_id' => $parcelId,
                    'type' => $type->value,
                    'meter_number' => self::METER_PREFIX.$code.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'installed_at' => '2023-01-01',
                    'start_reading' => $start,
                    'status' => MeterStatus::Active->value,
                    'notes' => 'Aktiver Demo-Zähler.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach (['2024-01-01', '2024-12-31', '2025-12-31', '2026-06-01'] as $readingIndex => $date) {
                    DB::table('meter_readings')->insert([
                        'meter_id' => $meterId,
                        'reading_value' => $start + $increments[$readingIndex],
                        'reading_date' => $date,
                        'source' => $readingIndex % 2 === 0
                            ? MeterReadingSource::Board->value
                            : MeterReadingSource::Tenant->value,
                        'notes' => 'Demo-Ablesung.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $meterIds[] = $meterId;
            }
        }

        $oldMeterId = DB::table('meters')->insertGetId([
            'parcel_id' => $parcelIds[2],
            'type' => MeterType::Water->value,
            'meter_number' => self::METER_PREFIX.'W-03-ALT',
            'installed_at' => '2020-01-01',
            'removed_at' => '2023-01-01',
            'start_reading' => 10,
            'end_reading' => 94,
            'status' => MeterStatus::Replaced->value,
            'notes' => 'Historischer Demo-Zählerwechsel.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('meter_readings')->insert([
            [
                'meter_id' => $oldMeterId,
                'reading_value' => 10,
                'reading_date' => '2020-01-01',
                'source' => MeterReadingSource::Board->value,
                'notes' => 'Einbaustand des alten Demo-Zählers.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'meter_id' => $oldMeterId,
                'reading_value' => 94,
                'reading_date' => '2023-01-01',
                'source' => MeterReadingSource::Board->value,
                'notes' => 'Ausbaustand des alten Demo-Zählers.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $meterIds[] = $oldMeterId;

        return $meterIds;
    }

    /**
     * @param  array<int, int>  $periods
     */
    private function seedBillingRates(array $periods, mixed $now): void
    {
        $amounts = [
            2024 => ['0.4200', '2.2000', '0.3200', '48.0000'],
            2025 => ['0.4400', '2.3000', '0.3300', '50.0000'],
            2026 => ['0.4600', '2.4000', '0.3400', '52.0000'],
        ];

        foreach ($periods as $year => $periodId) {
            foreach ([
                ['LEASE_PER_SQM', 'Pacht je m²', BillingRateType::PerSquareMeter, BillingRateScope::Parcel, $amounts[$year][0]],
                ['WATER_PER_M3', 'Wasser je m³', BillingRateType::PerCubicMeter, BillingRateScope::Parcel, $amounts[$year][1]],
                ['ELECTRICITY_PER_KWH', 'Strom je kWh', BillingRateType::PerKilowattHour, BillingRateScope::Parcel, $amounts[$year][2]],
                ['MEMBER_FEE', 'Mitgliedsbeitrag', BillingRateType::Fixed, BillingRateScope::Member, $amounts[$year][3]],
            ] as [$code, $name, $type, $scope, $amount]) {
                DB::table('billing_rates')->insert([
                    'billing_period_id' => $periodId,
                    'code' => $code,
                    'name' => $name,
                    'description' => 'Automatisch erzeugter Demo-Preis.',
                    'calculation_type' => $type->value,
                    'scope' => $scope->value,
                    'amount' => $amount,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function seedMeterReadingSubmission(int $meterId, int $userId, mixed $now): void
    {
        DB::table('meter_reading_submissions')->insert([
            'meter_id' => $meterId,
            'submitted_by' => $userId,
            'reading_value' => '205.5000',
            'reading_date' => '2026-06-10',
            'status' => MeterReadingSubmissionStatus::Pending->value,
            'notes' => 'Offene Demo-Zählerstandsmeldung.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param  array<int, int>  $periods
     * @param  list<int>  $parcelIds
     * @param  list<int>  $memberIds
     * @param  list<int>  $userIds
     */
    private function seedWorkData(
        array $periods,
        array $parcelIds,
        array $memberIds,
        array $userIds,
        mixed $now,
    ): void {
        $boardUserId = $userIds[0];

        foreach ($periods as $year => $periodId) {
            $eventHours = $year === 2024 ? '3.00' : ($year === 2025 ? '4.00' : '2.50');
            $eventId = DB::table('work_events')->insertGetId([
                'billing_period_id' => $periodId,
                'title' => "DEMO Gemeinschaftseinsatz {$year}",
                'description' => 'Pflege der Gemeinschaftsflächen und Wege.',
                'location' => 'Vereinsheim und Hauptweg',
                'starts_at' => "{$year}-05-18 09:00:00",
                'ends_at' => "{$year}-05-18 13:00:00",
                'status' => WorkEventStatus::Completed->value,
                'notes' => 'Automatisch erzeugter Demo-Arbeitseinsatz.',
                'created_by' => $boardUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($parcelIds as $index => $parcelId) {
                DB::table('work_event_participants')->insert([
                    'work_event_id' => $eventId,
                    'member_id' => $memberIds[$index],
                    'parcel_id' => $parcelId,
                    'status' => WorkEventParticipantStatus::Confirmed->value,
                    'hours' => $eventHours,
                    'notes' => 'Bestätigte Demo-Teilnahme.',
                    'confirmed_by' => $boardUserId,
                    'confirmed_at' => "{$year}-05-18 14:00:00",
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $manualHours = number_format(($index % 3) * 0.5, 2, '.', '');
                $submissionHours = $year === 2025 ? '1.50' : '0.00';
                $hoursDone = bcadd(bcadd($manualHours, $eventHours, 2), $submissionHours, 2);
                $hoursMissing = bccomp('10.00', $hoursDone, 2) > 0
                    ? bcsub('10.00', $hoursDone, 2)
                    : '0.00';

                DB::table('work_hours')->insert([
                    'billing_period_id' => $periodId,
                    'parcel_id' => $parcelId,
                    'hours_required' => '10.00',
                    'manual_hours_done' => $manualHours,
                    'event_hours_done' => $eventHours,
                    'submission_hours_done' => $submissionHours,
                    'hours_done' => $hoursDone,
                    'hours_missing' => $hoursMissing,
                    'penalty_rate' => '15.00',
                    'penalty_amount' => bcmul($hoursMissing, '15.00', 2),
                    'notes' => 'Automatisch erzeugtes Demo-Arbeitsstundenkonto.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($year === 2025) {
                    DB::table('work_hour_submissions')->insert([
                        'billing_period_id' => $periodId,
                        'parcel_id' => $parcelId,
                        'submitted_by' => $userIds[$index],
                        'worked_at' => '2025-08-09',
                        'hours' => '1.50',
                        'description' => 'Gemeinschaftshecke geschnitten und Schnittgut entsorgt.',
                        'status' => WorkHourSubmissionStatus::Approved->value,
                        'reviewed_by' => $boardUserId,
                        'reviewed_at' => '2025-08-10 10:00:00',
                        'review_note' => 'Demo-Meldung geprüft.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        foreach ([1, 2] as $index) {
            DB::table('work_hour_submissions')->insert([
                'billing_period_id' => $periods[2026],
                'parcel_id' => $parcelIds[$index],
                'submitted_by' => $userIds[$index],
                'worked_at' => '2026-06-01',
                'hours' => '1.00',
                'description' => 'DEMO Meldung: Pflege des Spielplatzumfelds.',
                'status' => WorkHourSubmissionStatus::Pending->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
