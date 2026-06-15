<?php

namespace App\Enums;

enum MailRecipientGroup: string
{
    case ActiveMembers = 'active_members';
    case CurrentTenants = 'current_tenants';
    case Board = 'board';
    case OpenInvoices = 'open_invoices';
    case MissingMeterReadings = 'missing_meter_readings';

    public function label(): string
    {
        return match ($this) {
            self::ActiveMembers => 'Alle aktiven Mitglieder',
            self::CurrentTenants => 'Alle aktuellen Pächter',
            self::Board => 'Administrator und Vorstand',
            self::OpenInvoices => 'Empfänger offener Rechnungen',
            self::MissingMeterReadings => 'Fehlende Endstände der letzten Abrechnungsperiode',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ActiveMembers => 'Aktive Mitglieder mit hinterlegter E-Mail-Adresse.',
            self::CurrentTenants => 'Mitglieder mit einer aktuell laufenden Pächterzuordnung.',
            self::Board => 'Bestätigte Konten mit Rolle Administrator oder Vorstand.',
            self::OpenInvoices => 'Mitglieder mit freigegebenen, offenen oder zurückgegebenen Rechnungen.',
            self::MissingMeterReadings => 'Pächter aktiver Zähler ohne Endstand zur letzten beendeten, noch bearbeitbaren Abrechnungsperiode.',
        };
    }

    public function isAvailable(): bool
    {
        return match ($this) {
            self::OpenInvoices => FeatureModule::Billing->enabled(),
            self::MissingMeterReadings => FeatureModule::Meters->enabled(),
            default => true,
        };
    }

    /**
     * @return list<self>
     */
    public static function availableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $group): bool => $group->isAvailable(),
        ));
    }
}
