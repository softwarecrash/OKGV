<?php

namespace App\Enums;

enum MeterStatus: string
{
    case Active = 'active';
    case Replaced = 'replaced';
    case Removed = 'removed';
    case Defective = 'defective';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::Replaced => 'Ersetzt',
            self::Removed => 'Ausgebaut',
            self::Defective => 'Defekt',
        };
    }
}
