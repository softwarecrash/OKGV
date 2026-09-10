<?php

namespace App\Enums;

enum GardenInspectionFindingStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';

    public function label(): string
    {
        return $this === self::Open ? 'Offen' : 'Erledigt';
    }
}
