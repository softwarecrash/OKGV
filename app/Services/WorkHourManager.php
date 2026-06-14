<?php

namespace App\Services;

use App\Models\BillingPeriod;
use App\Models\User;
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
                    ? $record->only(['hours_required', 'hours_done', 'penalty_rate'])
                    : null;

                $difference = bcsub(
                    (string) $data['hours_required'],
                    (string) $data['hours_done'],
                    2,
                );
                $missing = bccomp($difference, '0.00', 2) > 0
                    ? $difference
                    : '0.00';
                $penaltyAmount = $this->roundMoney(
                    bcmul($missing, (string) $data['penalty_rate'], 8),
                );

                $record->fill([
                    'billing_period_id' => $lockedPeriod->id,
                    'member_id' => $data['member_id'],
                    'hours_required' => $data['hours_required'],
                    'hours_done' => $data['hours_done'],
                    'hours_missing' => $missing,
                    'penalty_rate' => $data['penalty_rate'],
                    'penalty_amount' => $penaltyAmount,
                    'notes' => $data['notes'] ?? null,
                ])->save();

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

    private function roundMoney(string $value): string
    {
        $cents = bcdiv(bcadd(bcmul($value, '100', 8), '0.5', 8), '1', 0);

        return bcdiv($cents, '100', 2);
    }
}
