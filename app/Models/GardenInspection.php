<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['title', 'inspected_at', 'instructions', 'inspectors'])]
class GardenInspection extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            if ($model->getRawOriginal('finalized_at') !== null && array_diff(array_keys($model->getDirty()), ['updated_at']) !== []) {
                throw new LogicException('Finalized inspections are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Garden inspections must be retained.'));
    }

    protected function casts(): array
    {
        return ['inspected_at' => 'datetime', 'finalized_at' => 'datetime'];
    }

    public function findings(): HasMany
    {
        return $this->hasMany(GardenInspectionFinding::class);
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }
}
