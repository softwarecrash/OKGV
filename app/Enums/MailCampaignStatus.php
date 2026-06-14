<?php

namespace App\Enums;

enum MailCampaignStatus: string
{
    case Draft = 'draft';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Sending => 'Wird versendet',
            self::Sent => 'Versendet',
            self::Failed => 'Mit Fehlern beendet',
        };
    }
}
