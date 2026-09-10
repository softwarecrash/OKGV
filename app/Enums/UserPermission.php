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
    case ManageAnnouncements = 'manage_announcements';
    case ViewBoardWork = 'view_board_work';
    case ManageBoardWork = 'manage_board_work';
    case ViewTasks = 'view_tasks';
    case ManageTasks = 'manage_tasks';
    case ManageDocuments = 'manage_documents';
    case ManageWorkEvents = 'manage_work_events';
    case ManageWaitingList = 'manage_waiting_list';
    case ManageInventory = 'manage_inventory';
    case ManageDataTransfer = 'manage_data_transfer';
    case ManagePrivacy = 'manage_privacy';

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
            self::ManageAnnouncements => 'Bekanntmachungen verwalten',
            self::ViewBoardWork => 'Vertrauliche Vorstandsunterlagen lesen',
            self::ManageBoardWork => 'Sitzungen und Beschlüsse verwalten',
            self::ViewTasks => 'Eigene Verwaltungsaufgaben bearbeiten',
            self::ManageTasks => 'Aufgaben und Wiedervorlagen verwalten',
            self::ManageDocuments => 'Dokumente verwalten',
            self::ManageWorkEvents => 'Arbeitseinsätze verwalten',
            self::ManageWaitingList => 'Warteliste verwalten',
            self::ManageInventory => 'Inventar verwalten',
            self::ManageDataTransfer => 'CSV-Daten übertragen',
            self::ManagePrivacy => 'Datenschutzanfragen verwalten',
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
            self::ManageCommunication => 'Erlaubt Serienmails, Versandhistorie und allgemeine PDF-Briefe.',
            self::ManageAnnouncements => 'Erlaubt Beiträge am Schwarzen Brett zu veröffentlichen, zurückzuziehen und Lesebestätigungen einzusehen.',
            self::ViewBoardWork => 'Erlaubt Einsicht in interne Sitzungen, Protokolle und Beschlüsse sowie Erledigung eigener Beschlussaufgaben.',
            self::ManageBoardWork => 'Erlaubt Sitzungen, Protokollabschluss, Beschlüsse und deren Aufgaben zu verwalten. Umfasst vertrauliche Vorstandsunterlagen.',
            self::ViewTasks => 'Erlaubt zugewiesene Aufgaben zu lesen, zu beginnen und zu erledigen. Beschlussaufgaben benötigen zusätzlich Vorstandsleserecht.',
            self::ManageTasks => 'Erlaubt Aufgaben anzulegen, zuzuweisen, zu bearbeiten und abzubrechen. Vertrauliche Beschlüsse behalten ihre eigenen Rechte.',
            self::ManageDocuments => 'Erlaubt private Uploads, Dateiversionen, Freigaben und die zentrale Dokumentenverwaltung.',
            self::ManageWorkEvents => 'Erlaubt Termine, Teilnehmer und bestätigte Arbeitsstunden zu verwalten.',
            self::ManageWaitingList => 'Erlaubt den Zugriff auf Interessenten, Kontaktdaten, Prioritäten und Bearbeitungsstatus.',
            self::ManageInventory => 'Erlaubt Gegenstände, Ausgaben, Rückgaben und die Inventarhistorie zu verwalten.',
            self::ManageDataTransfer => 'Erlaubt geprüfte CSV-Importe und Exporte. Vollständige Backups bleiben Administratoren vorbehalten.',
            self::ManagePrivacy => 'Erlaubt Auskunftsexporte und die Prüfung von Löschanfragen. Die endgültige Pseudonymisierung bleibt Administratoren vorbehalten.',
        };
    }

    public function module(): ?FeatureModule
    {
        return match ($this) {
            self::ViewAllMeters,
            self::ManageMeters,
            self::CorrectMeterReadings,
            self::ReviewMeterReadingSubmissions => FeatureModule::Meters,
            self::ManageBilling,
            self::ManageBillingTemplates => FeatureModule::Billing,
            self::ManageSepa => FeatureModule::Sepa,
            self::ReviewTenantRegistrations => FeatureModule::TenantPortal,
            self::ManageCommunication => FeatureModule::Communication,
            self::ManageAnnouncements => FeatureModule::Announcements,
            self::ViewBoardWork, self::ManageBoardWork => FeatureModule::BoardWork,
            self::ViewTasks, self::ManageTasks => FeatureModule::Tasks,
            self::ManageDocuments => FeatureModule::Documents,
            self::ManageWorkEvents => FeatureModule::WorkEvents,
            self::ManageWaitingList => FeatureModule::WaitingList,
            self::ManageInventory => FeatureModule::Inventory,
            self::ManageDataTransfer => FeatureModule::DataTransfer,
            default => null,
        };
    }

    public function isAvailable(): bool
    {
        return $this->module()?->enabled() ?? true;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function availableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $permission): bool => $permission->isAvailable(),
        ));
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
            self::ManageTasks->value => self::ViewTasks->value,
            self::ManageBoardWork->value => self::ViewBoardWork->value,
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
