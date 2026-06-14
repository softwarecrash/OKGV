<?php

namespace App\Enums;

enum MailRecipientStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ausstehend',
            self::Sent => 'Versendet',
            self::Failed => 'Fehlgeschlagen',
        };
    }
}
