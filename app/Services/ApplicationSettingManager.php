<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class ApplicationSettingManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        ApplicationSetting $settings,
        array $data,
        ?UploadedFile $logo,
    ): ApplicationSetting {
        $removeLogo = (bool) ($data['remove_logo'] ?? false);
        $clearBankDetails = (bool) ($data['clear_bank_details'] ?? false);
        unset($data['logo'], $data['remove_logo'], $data['clear_bank_details']);

        $newIban = $this->nullableNormalized($data['bank_iban'] ?? null);
        $newBic = $this->nullableNormalized($data['bank_bic'] ?? null);

        if ($clearBankDetails) {
            $data['bank_account_holder'] = null;
            $data['bank_name'] = null;
            $data['bank_iban'] = null;
            $data['bank_iban_last_four'] = null;
            $data['bank_bic'] = null;
        } else {
            if ($newIban === null) {
                unset($data['bank_iban']);
            } else {
                $data['bank_iban'] = $newIban;
                $data['bank_iban_last_four'] = substr($newIban, -4);
            }

            if ($newBic === null) {
                unset($data['bank_bic']);
            } else {
                $data['bank_bic'] = $newBic;
            }
        }

        if ($logo !== null) {
            $data = [
                ...$data,
                'logo_path' => $logo->store('association/logo', 'local'),
                'logo_original_name' => $logo->getClientOriginalName(),
                'logo_mime' => $logo->getMimeType(),
                'logo_size' => $logo->getSize(),
            ];
        } elseif ($removeLogo) {
            $data = [
                ...$data,
                'logo_path' => null,
                'logo_original_name' => null,
                'logo_mime' => null,
                'logo_size' => null,
            ];
        }

        DB::transaction(fn () => $settings->update($data));

        return $settings->refresh();
    }

    private function nullableNormalized(mixed $value): ?string
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', (string) $value) ?? '');

        return $normalized === '' ? null : $normalized;
    }
}
