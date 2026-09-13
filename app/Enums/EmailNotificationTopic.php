<?php

namespace App\Enums;

enum EmailNotificationTopic: string
{
    case RegistrationRequests = 'registration_requests';
    case Announcements = 'announcements';
    case WorkEvents = 'work_events';
    case GardenInspections = 'garden_inspections';
    case Polls = 'polls';
    case MemberAssemblies = 'member_assemblies';

    public function label(): string
    {
        return match ($this) {
            self::RegistrationRequests => 'Neue Registrierungsanfragen',
            self::Announcements => 'Beiträge am Schwarzen Brett',
            self::WorkEvents => 'Neue Arbeitseinsätze',
            self::GardenInspections => 'Gartenbegehungen und Feststellungen',
            self::Polls => 'Neue Umfragen und Terminabfragen',
            self::MemberAssemblies => 'Einladungen zu Mitgliederversammlungen',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::RegistrationRequests => 'Wenn eine neue Zugangsanfrage geprüft und freigegeben werden muss.',
            self::Announcements => 'Wenn ein für dich sichtbarer Beitrag veröffentlicht wird.',
            self::WorkEvents => 'Wenn ein neuer Arbeitseinsatz geplant wird.',
            self::GardenInspections => 'Bei Feststellungen zu deiner Parzelle und künftig angekündigten Begehungen.',
            self::Polls => 'Wenn du zu einer Umfrage oder Terminabfrage eingeladen wirst.',
            self::MemberAssemblies => 'Wenn eine Mitgliederversammlung veröffentlicht wird.',
        };
    }
}
