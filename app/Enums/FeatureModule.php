<?php

namespace App\Enums;

enum FeatureModule: string
{
    case TenantPortal = 'tenant_portal';
    case Meters = 'meters';
    case Billing = 'billing';
    case WorkHours = 'work_hours';
    case WorkEvents = 'work_events';
    case Sepa = 'sepa';
    case Dunning = 'dunning';
    case Documents = 'documents';
    case Communication = 'communication';
    case WaitingList = 'waiting_list';
    case Inventory = 'inventory';

    public function label(): string
    {
        return match ($this) {
            self::TenantPortal => 'Pächterportal',
            self::Meters => 'Zählerverwaltung',
            self::Billing => 'Abrechnung',
            self::WorkHours => 'Arbeitsstunden',
            self::WorkEvents => 'Arbeitseinsätze',
            self::Sepa => 'SEPA',
            self::Dunning => 'Mahnwesen',
            self::Documents => 'Dokumentenverwaltung',
            self::Communication => 'Kommunikation',
            self::WaitingList => 'Warteliste',
            self::Inventory => 'Inventarverwaltung',
        };
    }

    /**
     * @return list<self>
     */
    public function dependencies(): array
    {
        return match ($this) {
            self::WorkHours => [self::Billing],
            self::WorkEvents => [self::WorkHours],
            self::Sepa, self::Dunning => [self::Billing],
            default => [],
        };
    }

    public function enabled(): bool
    {
        return (bool) config("modules.{$this->value}", true);
    }
}
