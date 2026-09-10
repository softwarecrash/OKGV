<?php

namespace App\Models;

use App\Enums\GardenInspectionFindingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['parcel_id', 'category', 'description', 'due_at', 'responsible_member_id', 'task_id', 'photo_path', 'photo_original_name', 'photo_mime', 'photo_size', 'internal_note'])]
class GardenInspectionFinding extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            if ($model->getRawOriginal('resolved_at') !== null && array_diff(array_keys($model->getDirty()), ['updated_at']) !== []) {
                throw new LogicException('Resolved findings are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Findings must be retained.'));
    }

    protected function casts(): array
    {
        return ['due_at' => 'date', 'status' => GardenInspectionFindingStatus::class, 'resolved_at' => 'datetime'];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(GardenInspection::class, 'garden_inspection_id');
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function responsibleMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'responsible_member_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
