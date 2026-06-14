<?php

namespace App\Enums;

enum SepaMandateType: string
{
    case Recurring = 'recurring';
    case OneOff = 'one_off';

    public function label(): string
    {
        return match ($this) {
            self::Recurring => 'Wiederkehrend',
            self::OneOff => 'Einmalig',
        };
    }
}
