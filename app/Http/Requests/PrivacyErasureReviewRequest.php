<?php

namespace App\Http\Requests;

use App\Models\PrivacyErasureRequest;
use Illuminate\Foundation\Http\FormRequest;

class PrivacyErasureReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $erasureRequest = $this->route('privacy_erasure_request');

        return $erasureRequest instanceof PrivacyErasureRequest
            && $this->user()->can('review', $erasureRequest);
    }

    protected function prepareForValidation(): void
    {
        $note = trim((string) $this->input('review_note'));
        $this->merge(['review_note' => $note === '' ? null : $note]);
    }

    public function rules(): array
    {
        return [
            'review_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
