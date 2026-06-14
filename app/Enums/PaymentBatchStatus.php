<?php

namespace App\Enums;

enum PaymentBatchStatus: string
{
    case Draft = 'draft';
    case Exported = 'exported';
    case Submitted = 'submitted';
    case Settled = 'settled';
    case PartiallyReturned = 'partially_returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Exported => 'Exportiert',
            self::Submitted => 'Bei Bank eingereicht',
            self::Settled => 'Gebucht',
            self::PartiallyReturned => 'Teilweise zurückgegeben',
            self::Cancelled => 'Storniert',
        };
    }
}
