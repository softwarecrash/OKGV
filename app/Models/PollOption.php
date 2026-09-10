<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

#[Fillable(['position', 'label', 'starts_at', 'ends_at'])]
class PollOption extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        $guard = function (PollOption $option): void {
            if (($option->exists && $option->isDirty('poll_id')) || Poll::whereKey($option->poll_id)->whereNotNull('published_at')->exists()) {
                throw new LogicException('Published options are immutable.');
            }
        };
        static::saving($guard);
        static::deleting($guard);
    }
}
