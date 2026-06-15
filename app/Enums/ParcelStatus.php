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

    public function mapColor(): string
    {
        return match ($this) {
            self::Free => '#2E7D32',
            self::Assigned => '#66BB6A',
            self::Reserved, self::Terminated => '#F9A825',
            self::Blocked => '#C62828',
        };
    }

    public function mapTextColor(): string
    {
        return $this === self::Assigned ? '#263238' : '#FFFFFF';
    }
}
