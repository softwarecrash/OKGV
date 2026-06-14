<?php

namespace App\Http\Requests;

use App\Models\Meter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeterReplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('replace', $this->route('meter'));
    }

    public function rules(): array
    {
        return [
            'replaced_at' => ['required', 'date'],
            'end_reading' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'meter_number' => ['required', 'string', 'max:100', Rule::unique(Meter::class)],
            'start_reading' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
