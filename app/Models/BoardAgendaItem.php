<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['position', 'title', 'description', 'minutes'])]
class BoardAgendaItem extends Model
{
    protected static function booted(): void
    {
        static::saving(function (BoardAgendaItem $item): void {
            $meetingId = $item->getRawOriginal('board_meeting_id') ?? $item->board_meeting_id;
            if (($item->exists && $item->isDirty('board_meeting_id'))
                || BoardMeeting::query()->whereKey($meetingId)->where(fn ($query) => $query->whereNotNull('finalized_at')->orWhereNotNull('archived_at'))->exists()) {
                throw new LogicException('Closed agendas are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Agenda history must be retained.'));
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(BoardMeeting::class, 'board_meeting_id');
    }
}
