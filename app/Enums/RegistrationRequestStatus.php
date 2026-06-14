<?php

namespace App\Enums;

enum RegistrationRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Offen',
            self::Approved => 'Freigegeben',
            self::Rejected => 'Abgelehnt',
        };
    }
}
