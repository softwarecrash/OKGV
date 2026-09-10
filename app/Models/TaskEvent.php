<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['user_id', 'action', 'changed_fields', 'created_at'])]
class TaskEvent extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Task events are immutable.'));
        static::deleting(fn () => throw new LogicException('Task events must be retained.'));
    }

    protected function casts(): array
    {
        return ['changed_fields' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->action) {
            'created' => 'Angelegt', 'updated' => 'Bearbeitet', 'started' => 'Bearbeitung begonnen',
            'completed' => 'Erledigt', 'cancelled' => 'Abgebrochen', 'archived' => 'Archiviert',
            'restored' => 'Aus Archiv zurückgeholt', 'repeated' => 'Folgetermin angelegt',
            default => 'Geändert',
        };
    }
}
