<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelDunningNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('dunning_notice'));
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cancellation_reason' => 'Stornierungsgrund',
        ];
    }
}
