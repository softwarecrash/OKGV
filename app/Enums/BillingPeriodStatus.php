<?php

namespace App\Enums;

enum BillingPeriodStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Approved = 'approved';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Calculated => 'Berechnet',
            self::Approved => 'Freigegeben',
            self::Archived => 'Archiviert',
        };
    }
}
