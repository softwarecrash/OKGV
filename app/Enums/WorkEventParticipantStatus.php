<?php

namespace App\Enums;

enum WorkEventParticipantStatus: string
{
    case Registered = 'registered';
    case Confirmed = 'confirmed';
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Angemeldet',
            self::Confirmed => 'Teilnahme bestätigt',
            self::Absent => 'Nicht teilgenommen',
        };
    }
}
