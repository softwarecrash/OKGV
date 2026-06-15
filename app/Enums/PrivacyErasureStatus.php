<?php

namespace App\Enums;

enum PrivacyErasureStatus: string
{
    case Pending = 'pending';
    case Blocked = 'blocked';
    case Ready = 'ready';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Prüfung ausstehend',
            self::Blocked => 'Aufbewahrung erforderlich',
            self::Ready => 'Pseudonymisierung zulässig',
            self::Completed => 'Pseudonymisiert',
            self::Rejected => 'Abgelehnt',
        };
    }
}
