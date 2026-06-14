<?php

namespace App\Services;

use App\Enums\BillingPeriodStatus;
use App\Enums\WorkEventParticipantStatus;
use App\Enums\WorkEventStatus;
use App\Enums\WorkHourSubmissionStatus;
use App\Models\ApplicationSetting;
use App\Models\BillingPeriod;
use App\Models\ParcelTenant;
use App\Models\User;
use App\Models\WorkEventParticipant;
use App\Models\WorkHour;
use App\Models\WorkHourSubmission;

final class WorkHourManager
{
    public function __construct(
        private readonly BillingPeriodManager $periodManager,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(
        BillingPeriod $period,
        array $data,
        User $actor,
        ?WorkHour $workHour = null,
    ): WorkHour {
        return $this->periodManager->changeCalculationInputs(
            $period,
            $actor,
            'work_hours_updated',
            function (BillingPeriod $lockedPeriod) use ($data, $actor, $workHour): WorkHour {
                $record = $workHour
                    ? WorkHour::query()->lockForUpdate()->findOrFail($workHour->id)
                    : new WorkHour;
                $wasRecentlyCreated = ! $record->exists;
                $before = $record->exists
                    ? $record->only(['hours_required', 'manual_hours_done', 'event_hours_done', 'penalty_rate'])
                    : null;

                $record->fill([
                    'billing_period_id' => $lockedPeriod->id,
                    'parcel_id' => $data['parcel_id'],
                    'hours_required' => $data['hours_required'],
                    'manual_hours_done' => $data['hours_done'],
                    'penalty_rate' => $data['penalty_rate'],
                    'notes' => $data['notes'] ?? null,
                ]);
                $this->recalculate($record, $lockedPeriod);

                AuditLogger::log(
                    action: $wasRecentlyCreated ? 'work_hours.created' : 'work_hours.updated',
                    actor: $actor,
                    subject: $record,
                    metadata: [
                        'before' => $before,
                        'hours_required' => $record->hours_required,
                        'hours_done' => $record->hours_done,
                        'hours_missing' => $record->hours_missing,
                        'penalty_rate' => $record->penalty_rate,
                        'penalty_amount' => $record->penalty_amount,
                    ],
                );

                return $record->refresh();
            },
        );
    }

    public function initializePeriod(BillingPeriod $period, User $actor): int
    {
        $parcelIds = ParcelTenant::query()
            ->activeOn($period->ends_at)
            ->pluck('parcel_id')
            ->unique();
        $existingParcelIds = WorkHour::query()
            ->where('billing_period_id', $period->id)
            ->whereIn('parcel_id', $parcelIds)
            ->pluck('parcel_id');
        $missingParcelIds = $parcelIds->diff($existingParcelIds);

        if ($missingParcelIds->isEmpty()) {
            return 0;
        }

        return $this->periodManager->changeCalculationInputs(
            $period,
            $actor,
            'work_hour_accounts_automatically_created',
            function (BillingPeriod $lockedPeriod) use ($actor, $missingParcelIds): int {
                $created = 0;

                foreach ($missingParcelIds as $parcelId) {
                    if ($this->createAccount($lockedPeriod, (int) $parcelId)) {
                        $created++;
                    }
                }

                AuditLogger::log(
                    'work_hours.accounts_automatically_created',
                    $actor,
                    $lockedPeriod,
                    ['created_count' => $created],
                );

                return $created;
            },
        );
    }

    public function synchronizeTenancy(ParcelTenant $tenancy, User $actor): int
    {
        $periods = BillingPeriod::query()
            ->whereIn('status', [
                BillingPeriodStatus::Draft->value,
                BillingPeriodStatus::Calculated->value,
            ])
            ->whereDate('ends_at', '>=', $tenancy->starts_at)
            ->when(
                $tenancy->ends_at,
                fn ($query) => $query->whereDate('ends_at', '<=', $tenancy->ends_at),
            )
            ->whereDoesntHave(
                'workHours',
                fn ($query) => $query->where('parcel_id', $tenancy->parcel_id),
            )
            ->get();
        $created = 0;

        foreach ($periods as $period) {
            $created += $this->periodManager->changeCalculationInputs(
                $period,
                $actor,
                'work_hour_account_automatically_created',
                function (BillingPeriod $lockedPeriod) use ($actor, $tenancy): int {
                    if (! $this->createAccount($lockedPeriod, $tenancy->parcel_id)) {
                        return 0;
                    }

                    AuditLogger::log(
                        'work_hours.account_automatically_created',
                        $actor,
                        $lockedPeriod,
                        [
                            'parcel_id' => $tenancy->parcel_id,
                            'parcel_tenant_id' => $tenancy->id,
                        ],
                    );

                    return 1;
                },
            );
        }

        return $created;
    }

    public function synchronizeParcel(
        BillingPeriod $period,
        int $parcelId,
        User $actor,
    ): WorkHour {
        $record = WorkHour::query()
            ->where('billing_period_id', $period->id)
            ->where('parcel_id', $parcelId)
            ->lockForUpdate()
            ->first();

        if (! $record) {
            $settings = ApplicationSetting::current();
            $record = new WorkHour([
                'billing_period_id' => $period->id,
                'parcel_id' => $parcelId,
                'hours_required' => $settings->default_work_hours_required,
                'manual_hours_done' => '0.00',
                'penalty_rate' => $settings->default_work_hour_penalty_rate,
            ]);
        }

        $before = $record->exists
            ? $record->only(['hours_done', 'event_hours_done', 'hours_missing', 'penalty_amount'])
            : null;
        $this->recalculate($record, $period);

        AuditLogger::log(
            action: 'work_hours.event_hours_synchronized',
            actor: $actor,
            subject: $record,
            metadata: [
                'before' => $before,
                'hours_done' => $record->hours_done,
                'event_hours_done' => $record->event_hours_done,
                'submission_hours_done' => $record->submission_hours_done,
                'hours_missing' => $record->hours_missing,
                'penalty_amount' => $record->penalty_amount,
            ],
        );

        return $record->refresh();
    }

    private function recalculate(WorkHour $record, BillingPeriod $period): void
    {
        $eventHours = WorkEventParticipant::query()
            ->where('parcel_id', $record->parcel_id)
            ->where('status', WorkEventParticipantStatus::Confirmed)
            ->whereHas('workEvent', fn ($query) => $query
                ->where('billing_period_id', $period->id)
                ->where('status', WorkEventStatus::Completed))
            ->sum('hours');
        $submissionHours = WorkHourSubmission::query()
            ->where('billing_period_id', $period->id)
            ->where('parcel_id', $record->parcel_id)
            ->where('status', WorkHourSubmissionStatus::Approved)
            ->sum('hours');
        $hoursDone = bcadd(
            bcadd(
                (string) ($record->manual_hours_done ?? '0.00'),
                (string) $eventHours,
                2,
            ),
            (string) $submissionHours,
            2,
        );
        $difference = bcsub((string) $record->hours_required, $hoursDone, 2);
        $missing = bccomp($difference, '0.00', 2) > 0
            ? $difference
            : '0.00';

        $record->fill([
            'event_hours_done' => $eventHours,
            'submission_hours_done' => $submissionHours,
            'hours_done' => $hoursDone,
            'hours_missing' => $missing,
            'penalty_amount' => $this->roundMoney(
                bcmul($missing, (string) $record->penalty_rate, 8),
            ),
        ])->save();
    }

    private function createAccount(BillingPeriod $period, int $parcelId): bool
    {
        $record = WorkHour::query()->firstOrNew([
            'billing_period_id' => $period->id,
            'parcel_id' => $parcelId,
        ]);

        if ($record->exists) {
            return false;
        }

        $settings = ApplicationSetting::current();
        $record->fill([
            'hours_required' => $settings->default_work_hours_required,
            'manual_hours_done' => '0.00',
            'event_hours_done' => '0.00',
            'submission_hours_done' => '0.00',
            'penalty_rate' => $settings->default_work_hour_penalty_rate,
        ]);
        $this->recalculate($record, $period);

        return true;
    }

    private function roundMoney(string $value): string
    {
        $cents = bcdiv(bcadd(bcmul($value, '100', 8), '0.5', 8), '1', 0);

        return bcdiv($cents, '100', 2);
    }
}
