<?php

namespace App\Enums;

enum InvoicePaymentStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Paid = 'paid';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::Pending => 'Lastschrift vorbereitet',
            self::Paid => 'Bezahlt',
            self::Returned => 'Rücklastschrift',
        };
    }
}
