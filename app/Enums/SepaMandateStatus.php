<?php

namespace App\Enums;

enum SepaMandateStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::Revoked => 'Widerrufen',
            self::Expired => 'Abgelaufen',
        };
    }
}
