<?php

namespace App\Http\Requests;

use App\Enums\WorkEventStatus;
use App\Models\WorkEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WorkEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workEvent = $this->route('work_event');

        return $workEvent instanceof WorkEvent
            ? $this->user()->can('update', $workEvent)
            : $this->user()->can('create', WorkEvent::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['required', Rule::enum(WorkEventStatus::class)],
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

                $period = $this->route('billing_period')
                    ?? $this->route('work_event')?->billingPeriod;
                $startsAt = $this->date('starts_at');
                $endsAt = $this->date('ends_at');

                if ($startsAt->toDateString() < $period->starts_at->toDateString()
                    || $endsAt->toDateString() > $period->ends_at->toDateString()) {
                    $validator->errors()->add(
                        'starts_at',
                        'Der Arbeitseinsatz muss vollständig innerhalb der Abrechnungsperiode liegen.',
                    );
                }

                if ($this->string('status')->toString() === WorkEventStatus::Completed->value
                    && $endsAt->isFuture()) {
                    $validator->errors()->add(
                        'status',
                        'Ein zukünftiger Arbeitseinsatz kann noch nicht abgeschlossen werden.',
                    );
                }
            },
        ];
    }
}
