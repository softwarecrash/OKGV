<?php

namespace App\Enums;

enum DocumentType: string
{
    case LeaseContract = 'lease_contract';
    case HandoverProtocol = 'handover_protocol';
    case Termination = 'termination';
    case Invoice = 'invoice';
    case Statute = 'statute';
    case Minutes = 'minutes';
    case Photo = 'photo';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LeaseContract => 'Pachtvertrag',
            self::HandoverProtocol => 'Übergabeprotokoll',
            self::Termination => 'Kündigung',
            self::Invoice => 'Rechnung oder Rechnungsbeleg',
            self::Statute => 'Satzung',
            self::Minutes => 'Protokoll',
            self::Photo => 'Foto',
            self::Other => 'Sonstiges',
        };
    }
}
