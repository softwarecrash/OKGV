<?php

namespace App\Enums;

enum BillingSettlementType: string
{
    case Advance = 'advance';
    case Arrears = 'arrears';

    public function label(): string
    {
        return match ($this) {
            self::Advance => 'Vorauszahlung',
            self::Arrears => 'Nachberechnung',
        };
    }
}
