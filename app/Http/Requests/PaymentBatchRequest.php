<?php

namespace App\Http\Requests;

use App\Models\PaymentBatch;
use Illuminate\Foundation\Http\FormRequest;

class PaymentBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PaymentBatch::class);
    }

    public function rules(): array
    {
        return [
            'requested_collection_date' => ['required', 'date', 'after_or_equal:today'],
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['required', 'integer', 'distinct', 'exists:invoices,id'],
        ];
    }
}
