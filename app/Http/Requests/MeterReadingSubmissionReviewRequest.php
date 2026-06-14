<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeterReadingSubmissionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('meter_reading_submission'));
    }

    public function rules(): array
    {
        return [
            'review_note' => [
                $this->routeIs('meter-reading-submissions.reject') ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
