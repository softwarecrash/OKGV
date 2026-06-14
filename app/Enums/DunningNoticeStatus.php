<?php

namespace App\Enums;

enum DunningNoticeStatus: string
{
    case Issued = 'issued';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Ausgestellt',
            self::Cancelled => 'Storniert',
        };
    }
}
