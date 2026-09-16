<?php

namespace App\Enums;

enum ParcelUseType: string
{
    case Lease = 'lease';
    case Ownership = 'ownership';

    public function label(): string
    {
        return match ($this) {
            self::Lease => 'Pachtparzelle',
            self::Ownership => 'Eigentumsparzelle',
        };
    }

    public function assignmentLabel(bool $primary): string
    {
        return match ($this) {
            self::Lease => $primary ? 'Hauptpächter' : 'Mitpächter',
            self::Ownership => $primary ? 'Eigentümer' : 'Miteigentümer',
        };
    }
}
