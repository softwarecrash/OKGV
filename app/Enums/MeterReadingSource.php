<?php

namespace App\Enums;

enum MeterReadingSource: string
{
    case Board = 'board';
    case Tenant = 'tenant';
    case Import = 'import';

    public function label(): string
    {
        return match ($this) {
            self::Board => 'Vorstand',
            self::Tenant => 'Pächter',
            self::Import => 'Import',
        };
    }
}
