<?php

namespace App\Enums;

enum MemberAssemblyMode: string
{
    case InPerson = 'in_person';
    case Hybrid = 'hybrid';
    case Virtual = 'virtual';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'Präsenzversammlung', self::Hybrid => 'Hybride Versammlung', self::Virtual => 'Virtuelle Versammlung',
        };
    }
}
