<?php

namespace App\Enums;

enum WorkEventStatus: string
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Geplant',
            self::Completed => 'Abgeschlossen',
            self::Cancelled => 'Abgesagt',
        };
    }
}
