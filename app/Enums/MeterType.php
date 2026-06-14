<?php

namespace App\Enums;

enum MeterType: string
{
    case Water = 'water';
    case Electricity = 'electricity';

    public function label(): string
    {
        return match ($this) {
            self::Water => 'Wasser',
            self::Electricity => 'Strom',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::Water => 'm³',
            self::Electricity => 'kWh',
        };
    }
}
