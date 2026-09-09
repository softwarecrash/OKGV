<?php

namespace App\Models;

use App\Enums\UserPermission;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['title', 'description', 'due_at', 'assigned_to'])]
class BoardFollowUp extends Model
{
    protected static function booted(): void
    {
        static::updating(function (BoardFollowUp $task): void {
            if ($task->getRawOriginal('completed_at') !== null
                || array_diff(array_keys($task->getDirty()), ['completed_at', 'completed_by', 'updated_at']) !== []) {
                throw new LogicException('Resolution tasks retain their original assignment.');
            }
        });
        static::deleting(fn () => throw new LogicException('Resolution tasks must be retained.'));
    }

    protected function casts(): array
    {
        return ['due_at' => 'date', 'completed_at' => 'datetime'];
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(BoardResolution::class, 'board_resolution_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeActionableFor(Builder $query, User $user): Builder
    {
        if (! $user->can('viewAny', BoardMeeting::class)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereNull('completed_at')->whereDate('due_at', '<=', today())
            ->when(! $user->hasPermission(UserPermission::ManageBoardWork), fn (Builder $query) => $query->where('assigned_to', $user->id));
    }
}
