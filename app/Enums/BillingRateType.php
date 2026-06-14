<?php

namespace App\Enums;

enum BillingRateType: string
{
    case Fixed = 'fixed';
    case PerSquareMeter = 'per_sqm';
    case PerKilowattHour = 'per_kwh';
    case PerCubicMeter = 'per_m3';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Festbetrag',
            self::PerSquareMeter => 'Je m²',
            self::PerKilowattHour => 'Je kWh',
            self::PerCubicMeter => 'Je m³',
            self::Manual => 'Manuell',
        };
    }
}
