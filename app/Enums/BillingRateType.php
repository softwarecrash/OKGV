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

    public function isAvailable(): bool
    {
        return ! in_array($this, [
            self::PerKilowattHour,
            self::PerCubicMeter,
        ], true) || FeatureModule::Meters->enabled();
    }

    /**
     * @return list<self>
     */
    public static function availableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $type): bool => $type->isAvailable(),
        ));
    }

    /**
     * @return list<string>
     */
    public static function unavailableValues(): array
    {
        return array_column(
            array_filter(self::cases(), fn (self $type): bool => ! $type->isAvailable()),
            'value',
        );
    }
}
