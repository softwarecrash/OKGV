<?php

namespace App\Models;

use App\Enums\TaskRecurrence;
use App\Enums\TaskStatus;
use App\Enums\UserPermission;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

#[Fillable(['title', 'description', 'due_at', 'assigned_to', 'member_id', 'parcel_id', 'document_id', 'remind_at', 'recurrence'])]
class Task extends Model
{
    use HasFactory;

    protected $table = 'board_follow_ups';

    protected static function booted(): void
    {
        static::updating(function (Task $task): void {
            if ($task->isDirty('board_resolution_id')
                || (($task->getRawOriginal('completed_at') !== null || $task->getRawOriginal('cancelled_at') !== null)
                    && array_diff(array_keys($task->getDirty()), ['archived_at', 'updated_at']) !== [])) {
                throw new LogicException('Closed tasks and resolution references are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Tasks must be archived.'));
    }

    protected function casts(): array
    {
        return ['due_at' => 'date', 'remind_at' => 'date', 'recurrence_anchor' => 'date', 'completed_at' => 'datetime', 'started_at' => 'datetime', 'cancelled_at' => 'datetime', 'archived_at' => 'datetime', 'recurrence' => TaskRecurrence::class];
    }

    public function status(): TaskStatus
    {
        return match (true) {
            $this->completed_at !== null => TaskStatus::Completed,
            $this->cancelled_at !== null => TaskStatus::Cancelled,
            $this->started_at !== null => TaskStatus::InProgress,
            default => TaskStatus::Open,
        };
    }

    public function isOpen(): bool
    {
        return $this->completed_at === null && $this->cancelled_at === null;
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(BoardResolution::class, 'board_resolution_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TaskEvent::class, 'task_id')->orderByDesc('id');
    }

    public function successor(): HasOne
    {
        return $this->hasOne(self::class, 'previous_task_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at')->whereNull('cancelled_at')->whereNull('archived_at');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->can('viewAny', self::class)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where(function (Builder $query) use ($user): void {
                $query->whereNull('board_resolution_id');
                if (! $user->hasPermission(UserPermission::ManageTasks)) {
                    $query->where('assigned_to', $user->id);
                    if (! $user->hasPermission(UserPermission::ViewTasks)) {
                        $query->whereRaw('1 = 0');
                    }
                }
            });
            if ($user->can('viewAny', BoardMeeting::class)) {
                $query->orWhereNotNull('board_resolution_id');
            }
        });
    }

    public function scopeActionableFor(Builder $query, User $user): Builder
    {
        return $query->visibleTo($user)->open()->whereDate(DB::raw('COALESCE(remind_at, due_at)'), '<=', today())
            ->where(function (Builder $query) use ($user): void {
                $query->where('assigned_to', $user->id);
                if ($user->hasPermission(UserPermission::ManageTasks)) {
                    $query->orWhereRaw('1 = 1');
                } elseif ($user->hasPermission(UserPermission::ManageBoardWork)) {
                    $query->orWhereNotNull('board_resolution_id');
                }
            });
    }
}
