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
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

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
                $hoursRequiredOverridden = ! $record->exists
                    || $record->hours_required_overridden
                    || bccomp(
                        (string) $data['hours_required'],
                        (string) $record->hours_required,
                        2,
                    ) !== 0;
                $before = $record->exists
                    ? $record->only([
                        'base_hours_required',
                        'hours_required',
                        'occupancy_factor',
                        'hours_required_overridden',
                        'manual_hours_done',
                        'event_hours_done',
                        'penalty_rate',
                    ])
                    : null;

                $record->fill([
                    'billing_period_id' => $lockedPeriod->id,
                    'parcel_id' => $data['parcel_id'],
                    'base_hours_required' => $record->base_hours_required
                        ?? $data['hours_required'],
                    'hours_required' => $data['hours_required'],
                    'occupancy_factor' => $record->occupancy_factor
                        ?? '1.00000000',
                    'hours_required_overridden' => $hoursRequiredOverridden,
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
                        'base_hours_required' => $record->base_hours_required,
                        'hours_required' => $record->hours_required,
                        'occupancy_factor' => $record->occupancy_factor,
                        'hours_required_overridden' => $record->hours_required_overridden,
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
            ->whereDate('starts_at', '<=', $period->ends_at)
            ->where(function ($query) use ($period): void {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $period->starts_at);
            })
            ->pluck('parcel_id')
            ->merge($period->workHours()->pluck('parcel_id'))
            ->unique()
            ->values();
        $changes = $parcelIds
            ->map(fn (int $parcelId): array => $this->accountSnapshot($period, $parcelId))
            ->filter(fn (array $snapshot): bool => $snapshot['requires_change'])
            ->values();

        if ($changes->isEmpty()) {
            return 0;
        }

        return $this->periodManager->changeCalculationInputs(
            $period,
            $actor,
            'work_hour_accounts_occupancy_synchronized',
            function (BillingPeriod $lockedPeriod) use ($actor, $changes): int {
                $created = 0;

                foreach ($changes as $snapshot) {
                    $created += $this->persistAccountSnapshot(
                        $lockedPeriod,
                        $snapshot,
                        $actor,
                    );
                }

                return $created;
            },
        );
    }

    public function synchronizeTenancy(ParcelTenant $tenancy, User $actor): int
    {
        return $this->synchronizeParcels([$tenancy->parcel_id], $actor);
    }

    /**
     * @param  list<int>  $parcelIds
     */
    public function synchronizeParcels(array $parcelIds, User $actor): int
    {
        $created = 0;
        $periods = BillingPeriod::query()
            ->whereIn('status', [
                BillingPeriodStatus::Draft->value,
                BillingPeriodStatus::Calculated->value,
            ])
            ->get();

        foreach ($periods as $period) {
            $changes = collect($parcelIds)
                ->unique()
                ->map(fn (int $parcelId): array => $this->accountSnapshot($period, $parcelId))
                ->filter(fn (array $snapshot): bool => $snapshot['requires_change'])
                ->values();

            if ($changes->isEmpty()) {
                continue;
            }

            $created += $this->periodManager->changeCalculationInputs(
                $period,
                $actor,
                'work_hour_accounts_occupancy_synchronized',
                function (BillingPeriod $lockedPeriod) use ($actor, $changes): int {
                    $count = 0;

                    foreach ($changes as $snapshot) {
                        $count += $this->persistAccountSnapshot(
                            $lockedPeriod,
                            $snapshot,
                            $actor,
                        );
                    }

                    return $count;
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
            $snapshot = $this->accountSnapshot($period, $parcelId);
            $this->persistAccountSnapshot($period, $snapshot, $actor);
            $record = WorkHour::query()
                ->where('billing_period_id', $period->id)
                ->where('parcel_id', $parcelId)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $before = $record->only([
            'hours_done',
            'event_hours_done',
            'submission_hours_done',
            'hours_missing',
            'penalty_amount',
        ]);
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
            'penalty_amount' => $this->roundTwoDecimals(
                bcmul($missing, (string) $record->penalty_rate, 8),
            ),
        ])->save();
    }

    /**
     * @return array{
     *     parcel_id: int,
     *     record: WorkHour|null,
     *     base_hours_required: string,
     *     hours_required: string,
     *     occupancy_factor: string,
     *     occupied_days: int,
     *     period_days: int,
     *     requires_change: bool
     * }
     */
    private function accountSnapshot(BillingPeriod $period, int $parcelId): array
    {
        $record = WorkHour::query()
            ->where('billing_period_id', $period->id)
            ->where('parcel_id', $parcelId)
            ->first();
        $occupancy = $this->occupancySnapshot($period, $parcelId);
        $settings = ApplicationSetting::current();
        $baseHours = (string) ($record?->base_hours_required
            ?? $settings->default_work_hours_required);
        $hoursRequired = $record?->hours_required_overridden
            ? (string) $record->hours_required
            : $this->proratedHours($baseHours, $occupancy['factor']);
        $requiresChange = (! $record && $occupancy['occupied_days'] > 0)
            || ($record && (
                bccomp((string) $record->occupancy_factor, $occupancy['factor'], 8) !== 0
                || (! $record->hours_required_overridden
                    && bccomp((string) $record->hours_required, $hoursRequired, 2) !== 0)
            ));

        return [
            'parcel_id' => $parcelId,
            'record' => $record,
            'base_hours_required' => $baseHours,
            'hours_required' => $hoursRequired,
            'occupancy_factor' => $occupancy['factor'],
            'occupied_days' => $occupancy['occupied_days'],
            'period_days' => $occupancy['period_days'],
            'requires_change' => $requiresChange,
        ];
    }

    /**
     * @param  array{
     *     parcel_id: int,
     *     record: WorkHour|null,
     *     base_hours_required: string,
     *     hours_required: string,
     *     occupancy_factor: string,
     *     occupied_days: int,
     *     period_days: int,
     *     requires_change: bool
     * }  $snapshot
     */
    private function persistAccountSnapshot(
        BillingPeriod $period,
        array $snapshot,
        User $actor,
    ): int {
        $record = $snapshot['record']
            ? WorkHour::query()->lockForUpdate()->findOrFail($snapshot['record']->id)
            : new WorkHour([
                'billing_period_id' => $period->id,
                'parcel_id' => $snapshot['parcel_id'],
                'manual_hours_done' => '0.00',
                'event_hours_done' => '0.00',
                'submission_hours_done' => '0.00',
                'penalty_rate' => ApplicationSetting::current()
                    ->default_work_hour_penalty_rate,
            ]);
        $wasCreated = ! $record->exists;

        $record->fill([
            'base_hours_required' => $snapshot['base_hours_required'],
            'hours_required' => $snapshot['hours_required'],
            'occupancy_factor' => $snapshot['occupancy_factor'],
            'hours_required_overridden' => $record->hours_required_overridden ?? false,
        ]);
        $this->recalculate($record, $period);

        AuditLogger::log(
            'work_hours.account_occupancy_synchronized',
            $actor,
            $record,
            [
                'created' => $wasCreated,
                'occupied_days' => $snapshot['occupied_days'],
                'period_days' => $snapshot['period_days'],
                'occupancy_factor' => $snapshot['occupancy_factor'],
                'base_hours_required' => $snapshot['base_hours_required'],
                'hours_required' => $snapshot['hours_required'],
                'hours_required_overridden' => $record->hours_required_overridden,
            ],
        );

        return $wasCreated ? 1 : 0;
    }

    /**
     * @return array{occupied_days: int, period_days: int, factor: string}
     */
    private function occupancySnapshot(BillingPeriod $period, int $parcelId): array
    {
        $ranges = ParcelTenant::query()
            ->where('parcel_id', $parcelId)
            ->whereDate('starts_at', '<=', $period->ends_at)
            ->where(function ($query) use ($period): void {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $period->starts_at);
            })
            ->orderBy('starts_at')
            ->get(['starts_at', 'ends_at'])
            ->map(function (ParcelTenant $tenancy) use ($period): array {
                $start = $tenancy->starts_at->gt($period->starts_at)
                    ? CarbonImmutable::instance($tenancy->starts_at)
                    : CarbonImmutable::instance($period->starts_at);
                $end = $tenancy->ends_at && $tenancy->ends_at->lt($period->ends_at)
                    ? CarbonImmutable::instance($tenancy->ends_at)
                    : CarbonImmutable::instance($period->ends_at);

                return ['start' => $start, 'end' => $end];
            });
        $occupiedDays = $this->mergeRanges($ranges)->sum(
            fn (array $range): int => $range['start']->diffInDays($range['end']) + 1,
        );
        $periodDays = $period->starts_at->diffInDays($period->ends_at) + 1;

        return [
            'occupied_days' => $occupiedDays,
            'period_days' => $periodDays,
            'factor' => bcdiv((string) $occupiedDays, (string) $periodDays, 8),
        ];
    }

    /**
     * @param  Collection<int, array{start: CarbonInterface, end: CarbonInterface}>  $ranges
     * @return Collection<int, array{start: CarbonInterface, end: CarbonInterface}>
     */
    private function mergeRanges(Collection $ranges): Collection
    {
        return $ranges->reduce(function (Collection $merged, array $range): Collection {
            $last = $merged->last();

            if (! $last || $range['start']->gt($last['end']->copy()->addDay())) {
                return $merged->push($range);
            }

            if ($range['end']->gt($last['end'])) {
                $merged->pop();
                $merged->push([
                    'start' => $last['start'],
                    'end' => $range['end'],
                ]);
            }

            return $merged;
        }, collect());
    }

    private function proratedHours(string $baseHours, string $factor): string
    {
        return $this->roundTwoDecimals(bcmul($baseHours, $factor, 8));
    }

    private function roundTwoDecimals(string $value): string
    {
        $cents = bcdiv(bcadd(bcmul($value, '100', 8), '0.5', 8), '1', 0);

        return bcdiv($cents, '100', 2);
    }
}
