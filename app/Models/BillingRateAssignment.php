<?php

namespace App\Models;

use Database\Factories\BillingRateAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'billing_rate_id',
    'member_id',
    'parcel_id',
    'quantity',
    'notes',
])]
class BillingRateAssignment extends Model
{
    /** @use HasFactory<BillingRateAssignmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function billingRate(): BelongsTo
    {
        return $this->belongsTo(BillingRate::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }
}
