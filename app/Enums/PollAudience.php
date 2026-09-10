<?php

namespace App\Enums;

enum PollAudience: string
{
    case Members = 'members';
    case Tenants = 'tenants';
    case Roles = 'roles';

    public function label(): string
    {
        return match ($this) {
            self::Members => 'Aktive Mitglieder', self::Tenants => 'Aktuelle Pächter', self::Roles => 'Ausgewählte Rollen'
        };
    }
}
