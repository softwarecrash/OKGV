<?php

namespace App\Http\Requests;

use App\Enums\WorkEventParticipantStatus;
use App\Enums\WorkEventStatus;
use App\Models\WorkEventParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WorkEventParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $participant = $this->route('work_event_participant');

        return $participant instanceof WorkEventParticipant
            ? $this->user()->can('update', $participant)
            : $this->user()->can('create', WorkEventParticipant::class);
    }

    public function rules(): array
    {
        $event = $this->route('work_event');
        $participant = $this->route('work_event_participant');

        return [
            'member_id' => [
                'required',
                'integer',
                Rule::exists('members', 'id')->whereNull('archived_at'),
                Rule::unique('work_event_participants', 'member_id')
                    ->where('work_event_id', $event?->id ?? $participant?->work_event_id)
                    ->ignore($participant),
            ],
            'status' => ['required', Rule::enum(WorkEventParticipantStatus::class)],
            'hours' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $event = $this->route('work_event')
                    ?? $this->route('work_event_participant')?->workEvent;
                $confirmed = $this->string('status')->toString()
                    === WorkEventParticipantStatus::Confirmed->value;

                if ($confirmed && $event->status !== WorkEventStatus::Completed) {
                    $validator->errors()->add(
                        'status',
                        'Eine Teilnahme kann erst nach Abschluss des Arbeitseinsatzes bestätigt werden.',
                    );
                }

                if ($confirmed && $this->float('hours') <= 0) {
                    $validator->errors()->add(
                        'hours',
                        'Für eine bestätigte Teilnahme müssen geleistete Stunden eingetragen werden.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $participant = $this->route('work_event_participant');
        $status = $this->input('status');

        $this->merge([
            'member_id' => $participant?->member_id ?? $this->input('member_id'),
            'hours' => $status === WorkEventParticipantStatus::Confirmed->value
                ? $this->input('hours')
                : 0,
        ]);
    }
}
