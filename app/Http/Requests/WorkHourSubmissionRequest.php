<?php

namespace App\Http\Requests;

use App\Models\WorkHourSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkHourSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WorkHourSubmission::class);
    }

    public function rules(): array
    {
        return [
            'parcel_id' => ['required', 'integer', Rule::exists('parcels', 'id')],
            'worked_at' => ['required', 'date', 'before_or_equal:today'],
            'hours' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:24'],
            'description' => ['required', 'string', 'max:1000'],
            'photo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:8192'],
        ];
    }
}
