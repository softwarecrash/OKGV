<?php

namespace App\Enums;

enum UserPermission: string
{
    case ViewAllMasterData = 'view_all_master_data';
    case ManageMasterData = 'manage_master_data';
    case ViewAllMeters = 'view_all_meters';
    case ManageMeters = 'manage_meters';
    case CorrectMeterReadings = 'correct_meter_readings';
    case ManageBilling = 'manage_billing';
    case ManageBillingTemplates = 'manage_billing_templates';
    case ManageSepa = 'manage_sepa';
    case ReviewTenantRegistrations = 'review_tenant_registrations';
    case ReviewMeterReadingSubmissions = 'review_meter_reading_submissions';
    case ManageCommunication = 'manage_communication';
    case ManageDocuments = 'manage_documents';

    public function label(): string
    {
        return match ($this) {
            self::ViewAllMasterData => 'Alle Mitglieder und Parzellen sehen',
            self::ManageMasterData => 'Mitglieder und Parzellen verwalten',
            self::ViewAllMeters => 'Alle Zähler und Zählerstände sehen',
            self::ManageMeters => 'Zähler und Zählerstände verwalten',
            self::CorrectMeterReadings => 'Gemeldete Zählerstände korrigieren',
            self::ManageBilling => 'Abrechnungen und Rechnungen verwalten',
            self::ManageBillingTemplates => 'Preisvorlagen verwalten',
            self::ManageSepa => 'SEPA-Daten und Lastschriften verwalten',
            self::ReviewTenantRegistrations => 'Registrierungsanfragen bearbeiten',
            self::ReviewMeterReadingSubmissions => 'Zählerstandsmeldungen prüfen',
            self::ManageCommunication => 'Kommunikation verwalten',
            self::ManageDocuments => 'Dokumente verwalten',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ViewAllMasterData => 'Erlaubt den lesenden Zugriff auf sämtliche Mitglieds- und Parzellendaten.',
            self::ManageMasterData => 'Erlaubt das Anlegen, Bearbeiten und Archivieren von Stammdaten.',
            self::ViewAllMeters => 'Erlaubt den lesenden Zugriff auf sämtliche Zähler und historische Stände.',
            self::ManageMeters => 'Erlaubt das Anlegen, Bearbeiten und Wechseln von Zählern sowie das Erfassen von Ständen.',
            self::CorrectMeterReadings => 'Erlaubt begründete, auditierte Korrekturen. Der ursprüngliche Wert bleibt erhalten.',
            self::ManageBilling => 'Erlaubt Berechnung, Freigabe und Verwaltung von Abrechnungen und Rechnungen.',
            self::ManageBillingTemplates => 'Erlaubt das Anlegen und Bearbeiten wiederverwendbarer Preisvorlagen.',
            self::ManageSepa => 'Erlaubt den Zugriff auf sensible Bankdaten, Mandate und Sammellastschriften.',
            self::ReviewTenantRegistrations => 'Erlaubt die Freigabe oder Ablehnung neuer Pächterkonten.',
            self::ReviewMeterReadingSubmissions => 'Erlaubt die Prüfung gemeldeter Zählerstände und Fotos.',
            self::ManageCommunication => 'Erlaubt Serienmails, Versandhistorie, SMTP-Tests und allgemeine PDF-Briefe.',
            self::ManageDocuments => 'Erlaubt private Uploads, Dateiversionen, Freigaben und die zentrale Dokumentenverwaltung.',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Add read permissions required by selected write permissions.
     *
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public static function expandDependencies(array $permissions): array
    {
        $dependencies = [
            self::ManageMasterData->value => self::ViewAllMasterData->value,
            self::ManageMeters->value => self::ViewAllMeters->value,
            self::CorrectMeterReadings->value => self::ViewAllMeters->value,
            self::ManageBillingTemplates->value => self::ManageBilling->value,
            self::ReviewMeterReadingSubmissions->value => self::ViewAllMeters->value,
        ];

        foreach ($dependencies as $permission => $dependency) {
            if (in_array($permission, $permissions, true)) {
                $permissions[] = $dependency;
            }
        }

        return array_values(array_unique($permissions));
    }
}
