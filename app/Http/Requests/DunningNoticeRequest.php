<?php

namespace App\Http\Requests;

use App\Models\DunningNotice;
use Illuminate\Foundation\Http\FormRequest;

class DunningNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DunningNotice::class);
    }

    public function rules(): array
    {
        return [
            'due_at' => ['required', 'date', 'after:today'],
            'fee_amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'due_at' => 'neue Zahlungsfrist',
            'fee_amount' => 'Mahngebühr',
            'note' => 'Hinweis',
        ];
    }
}
