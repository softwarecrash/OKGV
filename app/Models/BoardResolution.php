<?php

namespace App\Models;

use App\Enums\BoardResolutionResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['board_agenda_item_id', 'title', 'body', 'result', 'votes_for', 'votes_against', 'votes_abstained'])]
class BoardResolution extends Model
{
    protected static function booted(): void
    {
        static::saving(function (BoardResolution $resolution): void {
            $meetingId = $resolution->getRawOriginal('board_meeting_id') ?? $resolution->board_meeting_id;
            if (($resolution->exists && $resolution->isDirty('board_meeting_id'))
                || BoardMeeting::query()->whereKey($meetingId)->where(fn ($query) => $query->whereNotNull('finalized_at')->orWhereNotNull('archived_at'))->exists()) {
                throw new LogicException('Closed resolutions are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Resolutions must be retained.'));
    }

    protected function casts(): array
    {
        return ['result' => BoardResolutionResult::class];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(BoardMeeting::class, 'board_meeting_id');
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(BoardAgendaItem::class, 'board_agenda_item_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(BoardFollowUp::class);
    }
}
