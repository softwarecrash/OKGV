<?php

namespace App\Http\Requests;

use App\Enums\MeterReadingSource;
use App\Models\MeterReading;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MeterReading::class);
    }

    public function rules(): array
    {
        return [
            'meter_id' => ['required', 'integer', 'exists:meters,id'],
            'reading_value' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'reading_date' => [
                'required',
                'date',
                Rule::unique('meter_readings', 'reading_date')
                    ->where('meter_id', $this->integer('meter_id')),
            ],
            'source' => ['required', Rule::enum(MeterReadingSource::class)],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
