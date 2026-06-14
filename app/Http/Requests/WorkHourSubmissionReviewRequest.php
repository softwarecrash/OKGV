<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkHourSubmissionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('work_hour_submission'));
    }

    public function rules(): array
    {
        return [
            'review_note' => [
                $this->routeIs('work-hour-submissions.reject') ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
