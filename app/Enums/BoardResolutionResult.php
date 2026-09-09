<?php

namespace App\Enums;

enum BoardResolutionResult: string
{
    case Adopted = 'adopted';
    case Rejected = 'rejected';
    case Deferred = 'deferred';

    public function label(): string
    {
        return match ($this) {
            self::Adopted => 'Angenommen',
            self::Rejected => 'Abgelehnt',
            self::Deferred => 'Vertagt',
        };
    }
}
