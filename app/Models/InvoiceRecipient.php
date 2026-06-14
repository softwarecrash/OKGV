<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceRecipientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'invoice_id',
    'member_id',
    'member_number',
    'first_name',
    'last_name',
    'street',
    'zip',
    'city',
    'is_primary',
    'position',
])]
class InvoiceRecipient extends Model
{
    /** @use HasFactory<InvoiceRecipientFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        $guardApprovedInvoice = function (InvoiceRecipient $recipient): void {
            if ($recipient->invoice()->value('status') === InvoiceStatus::Approved->value) {
                throw new LogicException('Recipients of approved invoices cannot be changed.');
            }
        };

        static::creating($guardApprovedInvoice);
        static::updating($guardApprovedInvoice);
        static::deleting($guardApprovedInvoice);
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
