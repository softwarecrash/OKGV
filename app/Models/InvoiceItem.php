<?php

namespace App\Models;

use App\Enums\BillingRateType;
use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'invoice_id',
    'billing_rate_id',
    'parcel_id',
    'code',
    'description',
    'calculation_type',
    'quantity',
    'unit_price',
    'total_amount',
    'metadata',
])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        $guardApprovedInvoice = function (InvoiceItem $item): void {
            if ($item->invoice()->value('status') === InvoiceStatus::Approved->value) {
                throw new LogicException('Items of approved invoices cannot be changed.');
            }
        };

        static::creating($guardApprovedInvoice);
        static::updating($guardApprovedInvoice);
        static::deleting($guardApprovedInvoice);
    }

    protected function casts(): array
    {
        return [
            'calculation_type' => BillingRateType::class,
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'total_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function billingRate(): BelongsTo
    {
        return $this->belongsTo(BillingRate::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }
}
