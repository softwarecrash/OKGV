<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeterReadingCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canCorrectMeterReadings();
    }

    public function rules(): array
    {
        return [
            'corrected_value' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
