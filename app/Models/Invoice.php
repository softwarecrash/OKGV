<?php

namespace App\Models;

use App\Enums\DunningNoticeStatus;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'billing_period_id',
    'member_id',
    'invoice_number',
    'status',
    'payment_status',
    'issued_at',
    'due_at',
    'total_amount',
    'approved_at',
    'approved_by',
    'paid_at',
    'association_snapshot',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (Invoice $invoice): void {
            $allowedPaymentFields = ['payment_status', 'paid_at', 'updated_at'];
            $changedFields = array_keys($invoice->getDirty());

            if ($invoice->getRawOriginal('status') === InvoiceStatus::Approved->value
                && array_diff($changedFields, $allowedPaymentFields) !== []) {
                throw new LogicException('Approved invoices cannot be changed.');
            }
        });

        static::deleting(function (Invoice $invoice): void {
            if ($invoice->status === InvoiceStatus::Approved) {
                throw new LogicException('Approved invoices cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'payment_status' => InvoicePaymentStatus::class,
            'issued_at' => 'date',
            'due_at' => 'date',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'association_snapshot' => 'array',
        ];
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(InvoiceRecipient::class)->orderBy('position');
    }

    public function paymentBatchItems(): HasMany
    {
        return $this->hasMany(PaymentBatchItem::class);
    }

    public function dunningNotices(): HasMany
    {
        return $this->hasMany(DunningNotice::class)->orderByDesc('level');
    }

    public function activeDunningNotices(): HasMany
    {
        return $this->hasMany(DunningNotice::class)
            ->where('status', DunningNoticeStatus::Issued)
            ->orderByDesc('level');
    }

    public function primaryRecipient(): ?InvoiceRecipient
    {
        return $this->recipients->firstWhere('is_primary', true)
            ?? $this->recipients->first();
    }

    public function canReceivePaymentReminder(): bool
    {
        return $this->status === InvoiceStatus::Approved
            && $this->due_at->isPast()
            && in_array($this->payment_status, [
                InvoicePaymentStatus::Open,
                InvoicePaymentStatus::Returned,
            ], true);
    }
}
