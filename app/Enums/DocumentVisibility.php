<?php

namespace App\Enums;

enum DocumentVisibility: string
{
    case Internal = 'internal';
    case Tenant = 'tenant';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Intern',
            self::Tenant => 'Pächter',
            self::Public => 'Öffentlich',
        };
    }
}
