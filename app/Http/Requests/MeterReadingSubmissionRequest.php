<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeterReadingSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('submitReading', $this->route('meter'));
    }

    public function rules(): array
    {
        return [
            'reading_value' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'reading_date' => ['required', 'date', 'before_or_equal:today'],
            'photo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:8192'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
