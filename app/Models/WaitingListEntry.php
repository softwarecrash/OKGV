<?php

namespace App\Models;

use App\Enums\WaitingListStatus;
use Database\Factories\WaitingListEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

#[Fillable([
    'first_name',
    'last_name',
    'email',
    'phone',
    'mobile',
    'applied_at',
    'priority',
    'status',
    'notes',
])]
class WaitingListEntry extends Model
{
    /** @use HasFactory<WaitingListEntryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Waiting list entries cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'applied_at' => 'date',
            'priority' => 'integer',
            'status' => WaitingListStatus::class,
        ];
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        });
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
