<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['title', 'scheduled_at', 'location', 'attendees', 'minutes'])]
class BoardMeeting extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (BoardMeeting $meeting): void {
            if (($meeting->getRawOriginal('finalized_at') !== null || $meeting->getRawOriginal('archived_at') !== null)
                && array_diff(array_keys($meeting->getDirty()), ['archived_at', 'updated_at']) !== []) {
                throw new LogicException('Finalized board minutes are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Board meetings must be archived.'));
    }

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'finalized_at' => 'datetime', 'archived_at' => 'datetime', 'association_snapshot' => 'array'];
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(BoardAgendaItem::class)->orderBy('position');
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(BoardResolution::class)->orderBy('id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class);
    }

    public function statusLabel(): string
    {
        return $this->archived_at ? 'Archiviert' : ($this->finalized_at ? 'Abgeschlossen' : 'Entwurf');
    }
}
