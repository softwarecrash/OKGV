<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

#[Fillable(['user_id'])]
class PollParticipation extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['option_ids' => 'array', 'answered_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (PollParticipation $participation): void {
            if ($participation->getRawOriginal('answered_at') !== null || $participation->isDirty(['poll_id', 'user_id'])) {
                throw new LogicException('Submitted responses and participant references are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Participation records must be retained.'));
    }
}
