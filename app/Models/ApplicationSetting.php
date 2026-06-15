<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'system_name',
    'association_name',
    'street',
    'zip',
    'city',
    'contact_name',
    'phone',
    'email',
    'website',
    'logo_path',
    'logo_original_name',
    'logo_mime',
    'logo_size',
    'map_background_path',
    'map_background_original_name',
    'map_background_mime',
    'map_background_size',
    'map_background_width',
    'map_background_height',
    'map_background_source',
    'bank_account_holder',
    'bank_name',
    'bank_iban',
    'bank_iban_last_four',
    'bank_bic',
    'default_payment_term_days',
    'document_footer',
    'email_signature',
    'default_board_permission_profile_id',
    'default_work_hours_required',
    'default_work_hour_penalty_rate',
])]
class ApplicationSetting extends Model
{
    protected function casts(): array
    {
        return [
            'default_work_hours_required' => 'decimal:2',
            'default_work_hour_penalty_rate' => 'decimal:2',
            'bank_iban' => 'encrypted',
            'bank_bic' => 'encrypted',
            'default_payment_term_days' => 'integer',
            'map_background_width' => 'integer',
            'map_background_height' => 'integer',
        ];
    }

    public function defaultBoardPermissionProfile(): BelongsTo
    {
        return $this->belongsTo(
            PermissionProfile::class,
            'default_board_permission_profile_id',
        );
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'system_name' => config('app.name', 'OKGV'),
            'association_name' => config('app.name', 'OKGV'),
        ]);
    }

    public function getMaskedBankIbanAttribute(): ?string
    {
        return $this->bank_iban_last_four
            ? '•••• '.$this->bank_iban_last_four
            : null;
    }

    public function hasPostalAddress(): bool
    {
        return filled($this->street) && filled($this->zip) && filled($this->city);
    }
}
