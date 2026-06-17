<?php

namespace App\Models;

use App\Enums\SepaMandateStatus;
use App\Enums\SepaMandateType;
use Carbon\CarbonImmutable;
use Database\Factories\SepaMandateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'member_id',
    'mandate_reference',
    'iban',
    'iban_last_four',
    'bic',
    'account_holder',
    'signed_at',
    'valid_from',
    'valid_until',
    'mandate_type',
    'status',
    'last_used_at',
    'created_by',
    'revoked_at',
    'revoked_by',
    'revocation_note',
])]
class SepaMandate extends Model
{
    /** @use HasFactory<SepaMandateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'iban' => 'encrypted',
            'bic' => 'encrypted',
            'account_holder' => 'encrypted',
            'signed_at' => 'date',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'mandate_type' => SepaMandateType::class,
            'status' => SepaMandateStatus::class,
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function paymentBatchItems(): HasMany
    {
        return $this->hasMany(PaymentBatchItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isUsableOn(\DateTimeInterface|string $date): bool
    {
        $date = CarbonImmutable::parse($date)->startOfDay();

        return $this->status === SepaMandateStatus::Active
            && $this->valid_from->startOfDay()->lte($date)
            && ($this->valid_until === null || $this->valid_until->startOfDay()->gte($date));
    }

    protected function maskedIban(): Attribute
    {
        return Attribute::get(fn (): string => '•••• '.($this->iban_last_four ?: '----'));
    }
}
