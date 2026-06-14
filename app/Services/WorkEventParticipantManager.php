<?php

namespace App\Services;

use App\Enums\WorkEventParticipantStatus;
use App\Enums\WorkEventStatus;
use App\Models\User;
use App\Models\WorkEvent;
use App\Models\WorkEventParticipant;
use Illuminate\Validation\ValidationException;

final class WorkEventParticipantManager
{
    public function __construct(
        private readonly BillingPeriodManager $periodManager,
        private readonly WorkHourManager $workHourManager,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(
        WorkEvent $workEvent,
        array $data,
        User $actor,
        ?WorkEventParticipant $participant = null,
    ): WorkEventParticipant {
        return $this->periodManager->changeCalculationInputs(
            $workEvent->billingPeriod,
            $actor,
            'work_event_participant_updated',
            function ($lockedPeriod) use ($workEvent, $data, $actor, $participant): WorkEventParticipant {
                $event = WorkEvent::query()->lockForUpdate()->findOrFail($workEvent->id);

                if ($event->status === WorkEventStatus::Cancelled) {
                    throw ValidationException::withMessages([
                        'status' => 'Teilnahmen eines abgesagten Arbeitseinsatzes können nicht geändert werden.',
                    ]);
                }

                $record = $participant
                    ? WorkEventParticipant::query()->lockForUpdate()->findOrFail($participant->id)
                    : new WorkEventParticipant;
                $created = ! $record->exists;
                $before = $record->exists
                    ? $record->only(['status', 'hours'])
                    : null;
                $status = WorkEventParticipantStatus::from($data['status']);

                $record->fill([
                    'work_event_id' => $event->id,
                    'member_id' => $data['member_id'],
                    'parcel_id' => $data['parcel_id'],
                    'status' => $status,
                    'hours' => $data['hours'],
                    'notes' => $data['notes'] ?? null,
                    'confirmed_by' => $status === WorkEventParticipantStatus::Confirmed
                        ? $actor->id
                        : null,
                    'confirmed_at' => $status === WorkEventParticipantStatus::Confirmed
                        ? now()
                        : null,
                ])->save();

                $this->workHourManager->synchronizeParcel(
                    $lockedPeriod,
                    $record->parcel_id,
                    $actor,
                );

                AuditLogger::log(
                    action: $created
                        ? 'work_event_participant.created'
                        : 'work_event_participant.updated',
                    actor: $actor,
                    subject: $record,
                    metadata: [
                        'before' => $before,
                        'work_event_id' => $event->id,
                        'status' => $record->status->value,
                        'hours' => $record->hours,
                    ],
                );

                return $record->refresh();
            },
        );
    }
}
