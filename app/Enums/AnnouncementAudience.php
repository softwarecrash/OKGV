<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    case Public = 'public';
    case All = 'all';
    case Tenants = 'tenants';
    case Roles = 'roles';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Öffentlich im Internet',
            self::All => 'Alle freigegebenen Konten',
            self::Tenants => 'Alle aktuellen Pächter',
            self::Roles => 'Ausgewählte Rollen',
        };
    }
}
