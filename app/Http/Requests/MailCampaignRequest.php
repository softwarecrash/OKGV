<?php

namespace App\Http\Requests;

use App\Enums\MailRecipientGroup;
use App\Models\MailCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MailCampaign::class);
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'recipient_group' => [
                'required',
                Rule::in(array_column(MailRecipientGroup::availableCases(), 'value')),
            ],
        ];
    }
}
