<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'inventory_item_id',
    'member_id',
    'borrower_name',
    'issued_at',
    'due_at',
    'returned_at',
    'issued_by',
    'returned_by',
    'condition_on_issue',
    'condition_on_return',
    'notes',
])]
class InventoryLoan extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Inventory loans cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->returned_at === null
            && $this->due_at !== null
            && $this->due_at->isBefore(today());
    }
}
