<?php

namespace App\Http\Requests;

use App\Enums\SepaMandateStatus;
use App\Models\SepaMandate;
use Illuminate\Foundation\Http\FormRequest;

class PortalSepaMandateRevocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mandate = $this->route('sepaMandate');

        return $mandate instanceof SepaMandate
            && ($this->user()?->hasTenantAccess() ?? false)
            && $this->user()->member?->id === $mandate->member_id
            && $mandate->status === SepaMandateStatus::Active;
    }

    public function rules(): array
    {
        return [
            'revocation_note' => ['nullable', 'string', 'max:1000'],
            'confirm_revoke' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'revocation_note' => 'Widerrufsnotiz',
            'confirm_revoke' => 'Widerrufsbestätigung',
        ];
    }
}
