<?php

namespace App\Enums;

enum ParcelStatus: string
{
    case Free = 'free';
    case Assigned = 'assigned';
    case Reserved = 'reserved';
    case Terminated = 'terminated';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Frei',
            self::Assigned => 'Vergeben',
            self::Reserved => 'Reserviert',
            self::Terminated => 'Gekündigt',
            self::Blocked => 'Gesperrt',
        };
    }
}
