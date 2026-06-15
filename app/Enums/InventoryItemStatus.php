<?php

namespace App\Enums;

enum InventoryItemStatus: string
{
    case Available = 'available';
    case Issued = 'issued';
    case Maintenance = 'maintenance';
    case Retired = 'retired';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Verfügbar',
            self::Issued => 'Ausgegeben',
            self::Maintenance => 'Wartung',
            self::Retired => 'Ausgemustert',
            self::Lost => 'Verloren',
        };
    }

    /**
     * @return list<self>
     */
    public static function manuallySelectable(): array
    {
        return [
            self::Available,
            self::Maintenance,
            self::Retired,
            self::Lost,
        ];
    }

    /**
     * @return list<self>
     */
    public static function returnStatuses(): array
    {
        return [
            self::Available,
            self::Maintenance,
            self::Lost,
        ];
    }
}
