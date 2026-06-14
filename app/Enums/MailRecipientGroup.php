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
            self::MissingMeterReadings => 'Fehlende Zählerstände im laufenden Jahr',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ActiveMembers => 'Aktive Mitglieder mit hinterlegter E-Mail-Adresse.',
            self::CurrentTenants => 'Mitglieder mit einer aktuell laufenden Pächterzuordnung.',
            self::Board => 'Bestätigte Konten mit Rolle Administrator oder Vorstand.',
            self::OpenInvoices => 'Mitglieder mit freigegebenen, offenen oder zurückgegebenen Rechnungen.',
            self::MissingMeterReadings => 'Aktuelle Pächter aktiver Zähler ohne Ablesung seit Jahresbeginn.',
        };
    }
}
