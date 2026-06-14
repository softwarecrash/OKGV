<?php

namespace App\Enums;

enum MemberStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Resigned = 'resigned';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::Inactive => 'Inaktiv',
            self::Resigned => 'Ausgetreten',
            self::Archived => 'Archiviert',
        };
    }
}
