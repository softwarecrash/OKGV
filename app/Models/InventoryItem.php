<?php

namespace App\Models;

use App\Enums\InventoryItemStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'inventory_number',
    'name',
    'category',
    'description',
    'status',
    'location',
    'purchased_at',
    'purchase_price',
    'serial_number',
    'notes',
])]
class InventoryItem extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Inventory items cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'status' => InventoryItemStatus::class,
            'purchased_at' => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(InventoryLoan::class)->latest('issued_at')->latest('id');
    }

    public function openLoans(): HasMany
    {
        return $this->hasMany(InventoryLoan::class)->whereNull('returned_at');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('inventory_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        });
    }
}
