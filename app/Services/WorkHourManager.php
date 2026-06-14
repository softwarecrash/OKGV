<?php

namespace App\Services;

use App\Enums\WorkEventParticipantStatus;
use App\Enums\WorkEventStatus;
use App\Models\BillingPeriod;
use App\Models\User;
use App\Models\WorkEventParticipant;
use App\Models\WorkHour;

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
                    'member_id' => $data['member_id'],
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

    public function synchronizeMember(
        BillingPeriod $period,
        int $memberId,
        User $actor,
    ): WorkHour {
        $record = WorkHour::query()
            ->where('billing_period_id', $period->id)
            ->where('member_id', $memberId)
            ->lockForUpdate()
            ->first();

        if (! $record) {
            $record = new WorkHour([
                'billing_period_id' => $period->id,
                'member_id' => $memberId,
                'hours_required' => '0.00',
                'manual_hours_done' => '0.00',
                'penalty_rate' => '0.00',
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
                'hours_missing' => $record->hours_missing,
                'penalty_amount' => $record->penalty_amount,
            ],
        );

        return $record->refresh();
    }

    private function recalculate(WorkHour $record, BillingPeriod $period): void
    {
        $eventHours = WorkEventParticipant::query()
            ->where('member_id', $record->member_id)
            ->where('status', WorkEventParticipantStatus::Confirmed)
            ->whereHas('workEvent', fn ($query) => $query
                ->where('billing_period_id', $period->id)
                ->where('status', WorkEventStatus::Completed))
            ->sum('hours');
        $hoursDone = bcadd(
            (string) ($record->manual_hours_done ?? '0.00'),
            (string) $eventHours,
            2,
        );
        $difference = bcsub((string) $record->hours_required, $hoursDone, 2);
        $missing = bccomp($difference, '0.00', 2) > 0
            ? $difference
            : '0.00';

        $record->fill([
            'event_hours_done' => $eventHours,
            'hours_done' => $hoursDone,
            'hours_missing' => $missing,
            'penalty_amount' => $this->roundMoney(
                bcmul($missing, (string) $record->penalty_rate, 8),
            ),
        ])->save();
    }

    private function roundMoney(string $value): string
    {
        $cents = bcdiv(bcadd(bcmul($value, '100', 8), '0.5', 8), '1', 0);

        return bcdiv($cents, '100', 2);
    }
}
