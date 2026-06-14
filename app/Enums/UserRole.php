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

    /**
     * @return list<string>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Administrator => UserPermission::values(),
            self::Board => [
                UserPermission::ViewAllMasterData->value,
                UserPermission::ManageMasterData->value,
                UserPermission::ViewAllMeters->value,
                UserPermission::ManageMeters->value,
                UserPermission::ManageBilling->value,
                UserPermission::ManageBillingTemplates->value,
                UserPermission::ManageSepa->value,
                UserPermission::ReviewTenantRegistrations->value,
                UserPermission::ReviewMeterReadingSubmissions->value,
                UserPermission::ManageCommunication->value,
            ],
            self::Treasurer => [
                UserPermission::ViewAllMasterData->value,
                UserPermission::ViewAllMeters->value,
                UserPermission::ManageBilling->value,
                UserPermission::ManageSepa->value,
            ],
            self::WaterManager => [
                UserPermission::ViewAllMasterData->value,
                UserPermission::ViewAllMeters->value,
                UserPermission::ManageMeters->value,
                UserPermission::ReviewMeterReadingSubmissions->value,
            ],
            self::GardenManager => [
                UserPermission::ViewAllMasterData->value,
                UserPermission::ViewAllMeters->value,
            ],
            self::Tenant => [],
        };
    }

    public function canManageMasterData(): bool
    {
        return in_array(UserPermission::ManageMasterData->value, $this->defaultPermissions(), true);
    }

    public function canViewAllMasterData(): bool
    {
        return in_array(UserPermission::ViewAllMasterData->value, $this->defaultPermissions(), true);
    }

    public function canManageMeters(): bool
    {
        return in_array(UserPermission::ManageMeters->value, $this->defaultPermissions(), true);
    }

    public function canViewAllMeters(): bool
    {
        return in_array(UserPermission::ViewAllMeters->value, $this->defaultPermissions(), true);
    }

    public function canManageBilling(): bool
    {
        return in_array(UserPermission::ManageBilling->value, $this->defaultPermissions(), true);
    }

    public function canManageBillingTemplates(): bool
    {
        return in_array(UserPermission::ManageBillingTemplates->value, $this->defaultPermissions(), true);
    }

    public function canManageSepa(): bool
    {
        return in_array(UserPermission::ManageSepa->value, $this->defaultPermissions(), true);
    }

    public function canReviewTenantRegistrations(): bool
    {
        return in_array(UserPermission::ReviewTenantRegistrations->value, $this->defaultPermissions(), true);
    }

    public function canReviewMeterReadingSubmissions(): bool
    {
        return in_array(UserPermission::ReviewMeterReadingSubmissions->value, $this->defaultPermissions(), true);
    }
}
