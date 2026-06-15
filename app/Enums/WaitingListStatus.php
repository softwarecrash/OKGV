<?php

namespace App\Enums;

enum WaitingListStatus: string
{
    case Waiting = 'waiting';
    case Contacted = 'contacted';
    case Offered = 'offered';
    case Accepted = 'accepted';
    case Withdrawn = 'withdrawn';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Wartend',
            self::Contacted => 'Kontaktiert',
            self::Offered => 'Angebot unterbreitet',
            self::Accepted => 'Übernommen',
            self::Withdrawn => 'Zurückgezogen',
            self::Rejected => 'Abgelehnt',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [
            self::Waiting,
            self::Contacted,
            self::Offered,
        ], true);
    }

    /**
     * @return list<string>
     */
    public static function openValues(): array
    {
        return array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->isOpen()),
        );
    }
}
