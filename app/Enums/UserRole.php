<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Board = 'board';
    case Treasurer = 'treasurer';
    case WaterManager = 'water_manager';
    case GardenManager = 'garden_manager';
    case Tenant = 'tenant';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Board => 'Vorstand',
            self::Treasurer => 'Kassierer',
            self::WaterManager => 'Wasserwart',
            self::GardenManager => 'Gartenwart',
            self::Tenant => 'Pächter',
        };
    }

    public function canManageMasterData(): bool
    {
        return in_array($this, [self::Administrator, self::Board], true);
    }

    public function canViewAllMasterData(): bool
    {
        return $this !== self::Tenant;
    }
}
