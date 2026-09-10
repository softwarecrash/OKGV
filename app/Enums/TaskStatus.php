<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen', self::InProgress => 'In Bearbeitung',
            self::Completed => 'Erledigt', self::Cancelled => 'Abgebrochen',
        };
    }
}
