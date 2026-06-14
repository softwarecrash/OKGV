<?php

namespace App\Enums;

enum PaymentBatchItemStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Settled = 'settled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Vorbereitet',
            self::Submitted => 'Eingereicht',
            self::Settled => 'Gebucht',
            self::Returned => 'Rücklastschrift',
        };
    }
}
