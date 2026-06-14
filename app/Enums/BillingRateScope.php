<?php

namespace App\Enums;

enum BillingRateScope: string
{
    case Member = 'member';
    case Parcel = 'parcel';
    case Assignment = 'assignment';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Je Mitglied',
            self::Parcel => 'Je Parzelle',
            self::Assignment => 'Nach Zuordnung',
        };
    }
}
