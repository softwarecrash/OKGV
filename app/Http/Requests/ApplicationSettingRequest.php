<?php

namespace App\Http\Requests;

use App\Enums\FeatureModule;
use App\Models\ApplicationSetting;
use App\Rules\ValidIban;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', ApplicationSetting::class);
    }

    public function rules(): array
    {
        return [
            'system_name' => ['required', 'string', 'max:80'],
            'association_name' => ['required', 'string', 'max:150'],
            'street' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:150'],
            'contact_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'logo' => [
                'nullable',
                'file',
                'max:2048',
                'extensions:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
            'remove_logo' => ['required', 'boolean'],
            'bank_account_holder' => ['nullable', 'string', 'max:150'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_iban' => ['nullable', 'string', 'max:34', new ValidIban],
            'bank_bic' => ['nullable', 'string', 'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/'],
            'clear_bank_details' => ['required', 'boolean'],
            'default_payment_term_days' => ['required', 'integer', 'between:1,365'],
            'document_footer' => ['nullable', 'string', 'max:1000'],
            'email_signature' => ['nullable', 'string', 'max:2000'],
            'default_board_permission_profile_id' => [
                'required',
                Rule::exists('permission_profiles', 'id')->where('is_active', true),
            ],
            'default_work_hours_required' => [
                Rule::excludeIf(! FeatureModule::WorkHours->enabled()),
                'required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99',
            ],
            'default_work_hour_penalty_rate' => [
                Rule::excludeIf(! FeatureModule::WorkHours->enabled()),
                'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'system_name' => 'Systemname',
            'association_name' => 'Vereinsname',
            'street' => 'Straße und Hausnummer',
            'zip' => 'Postleitzahl',
            'city' => 'Ort',
            'contact_name' => 'Ansprechpartner',
            'phone' => 'Telefonnummer',
            'email' => 'Vereins-E-Mail-Adresse',
            'website' => 'Vereinswebseite',
            'logo' => 'Vereinslogo',
            'bank_account_holder' => 'Kontoinhaber',
            'bank_name' => 'Kreditinstitut',
            'bank_iban' => 'IBAN',
            'bank_bic' => 'BIC',
            'default_payment_term_days' => 'Standard-Zahlungsziel',
            'document_footer' => 'Dokumentfußzeile',
            'email_signature' => 'E-Mail-Signatur',
            'default_board_permission_profile_id' => 'Standardvorlage für Vorstände',
            'default_work_hours_required' => 'Pflichtstunden je Parzelle',
            'default_work_hour_penalty_rate' => 'Betrag je Fehlstunde',
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = [
            'phone',
            'website',
            'bank_account_holder',
            'bank_name',
            'bank_iban',
            'bank_bic',
            'document_footer',
            'email_signature',
        ];
        $normalized = [
            'system_name' => trim((string) $this->input('system_name')),
            'association_name' => trim((string) $this->input('association_name')),
            'street' => trim((string) $this->input('street')),
            'zip' => trim((string) $this->input('zip')),
            'city' => trim((string) $this->input('city')),
            'contact_name' => trim((string) $this->input('contact_name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'remove_logo' => $this->boolean('remove_logo'),
            'clear_bank_details' => $this->boolean('clear_bank_details'),
        ];

        foreach ($nullableFields as $field) {
            $value = trim((string) $this->input($field));
            $normalized[$field] = $value === '' ? null : $value;
        }

        $this->merge($normalized);
    }
}
