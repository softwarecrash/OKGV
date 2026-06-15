<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeterReadingSubmissionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('meter_reading_submission'));
    }

    public function rules(): array
    {
        return [
            'submission_id' => [
                'required',
                'integer',
                Rule::in([$this->route('meter_reading_submission')->id]),
            ],
            'review_note' => [
                $this->routeIs('meter-reading-submissions.reject') ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'submission_id' => $this->route('meter_reading_submission')->id,
        ]);
    }
}
