<?php

namespace App\Services;

use App\Models\BillingPeriod;
use App\Models\User;
use App\Models\WorkEvent;

final class WorkEventManager
{
    public function __construct(
        private readonly BillingPeriodManager $periodManager,
        private readonly WorkHourManager $workHourManager,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(
        BillingPeriod $period,
        array $data,
        User $actor,
        ?WorkEvent $workEvent = null,
    ): WorkEvent {
        return $this->periodManager->changeCalculationInputs(
            $period,
            $actor,
            'work_event_updated',
            function (BillingPeriod $lockedPeriod) use ($data, $actor, $workEvent): WorkEvent {
                $event = $workEvent
                    ? WorkEvent::query()->lockForUpdate()->findOrFail($workEvent->id)
                    : new WorkEvent;
                $created = ! $event->exists;
                $before = $event->exists
                    ? $event->only(['title', 'starts_at', 'ends_at', 'status'])
                    : null;

                $event->fill([
                    ...$data,
                    'billing_period_id' => $lockedPeriod->id,
                    'created_by' => $event->created_by ?? $actor->id,
                ])->save();

                if (! $created) {
                    $memberIds = $event->participants()->pluck('member_id')->unique();

                    foreach ($memberIds as $memberId) {
                        $this->workHourManager->synchronizeMember(
                            $lockedPeriod,
                            (int) $memberId,
                            $actor,
                        );
                    }
                }

                AuditLogger::log(
                    action: $created ? 'work_event.created' : 'work_event.updated',
                    actor: $actor,
                    subject: $event,
                    metadata: [
                        'before' => $before,
                        'status' => $event->status->value,
                        'starts_at' => $event->starts_at->toIso8601String(),
                        'ends_at' => $event->ends_at->toIso8601String(),
                    ],
                );

                return $event->refresh();
            },
        );
    }
}
