<?php

namespace App\Enums;

enum WorkHourSubmissionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Wird geprüft',
            self::Approved => 'Bestätigt',
            self::Rejected => 'Abgelehnt',
        };
    }
}
