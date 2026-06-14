<?php

namespace App\Http\Requests;

use App\Models\WorkHour;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workHour = $this->route('work_hour');

        return $workHour instanceof WorkHour
            ? $this->user()->can('update', $workHour)
            : $this->user()->can('create', WorkHour::class);
    }

    public function rules(): array
    {
        $period = $this->route('billing_period');
        $workHour = $this->route('work_hour');

        return [
            'parcel_id' => [
                'required',
                'integer',
                Rule::exists('parcels', 'id'),
                Rule::unique('work_hours', 'parcel_id')
                    ->where('billing_period_id', $period?->id ?? $workHour?->billing_period_id)
                    ->ignore($workHour),
            ],
            'hours_required' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'hours_done' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'penalty_rate' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $workHour = $this->route('work_hour');

        if ($workHour instanceof WorkHour) {
            $this->merge(['parcel_id' => $workHour->parcel_id]);
        }
    }
}
