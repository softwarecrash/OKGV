<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Storage;

final class AssociationDocumentProfile
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        return $this->resolve($this->snapshot());
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $settings = ApplicationSetting::current();

        return [
            'name' => $settings->association_name ?: $settings->system_name,
            'street' => $settings->street,
            'zip' => $settings->zip,
            'city' => $settings->city,
            'contact_name' => $settings->contact_name,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'website' => $settings->website,
            'bank_account_holder' => $settings->bank_account_holder,
            'bank_name' => $settings->bank_name,
            'bank_iban' => $settings->bank_iban,
            'bank_bic' => $settings->bank_bic,
            'document_footer' => $settings->document_footer,
            'email_signature' => $settings->email_signature,
            'logo_path' => $settings->logo_path,
            'logo_mime' => $settings->logo_mime,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>
     */
    public function resolve(?array $snapshot): array
    {
        $snapshot ??= $this->snapshot();
        $path = $snapshot['logo_path'] ?? null;
        $mime = $snapshot['logo_mime'] ?? null;
        $logoDataUri = null;

        if (is_string($path)
            && is_string($mime)
            && Storage::disk('local')->exists($path)) {
            $logoDataUri = sprintf(
                'data:%s;base64,%s',
                $mime,
                base64_encode(Storage::disk('local')->get($path)),
            );
        }

        return [
            ...$snapshot,
            'logo_data_uri' => $logoDataUri,
        ];
    }
}
