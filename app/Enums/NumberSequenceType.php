<?php

namespace App\Enums;

enum NumberSequenceType: string
{
    case Member = 'member';
    case Invoice = 'invoice';
    case SepaMandate = 'sepa_mandate';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Mitgliedsnummer',
            self::Invoice => 'Rechnungsnummer',
            self::SepaMandate => 'Mandatsreferenz',
            self::Document => 'Dokumentnummer',
        };
    }

    public function defaultFormat(): string
    {
        return match ($this) {
            self::Member => 'M-{NUMMER}',
            self::Invoice => '{JAHR}-{NUMMER}',
            self::SepaMandate => 'MANDAT-{JAHR}-{NUMMER}',
            self::Document => 'DOK-{JAHR}-{NUMMER}',
        };
    }

    public function defaultPadding(): int
    {
        return match ($this) {
            self::Member, self::SepaMandate => 4,
            self::Invoice, self::Document => 5,
        };
    }

    public function resetsYearlyByDefault(): bool
    {
        return $this !== self::Member;
    }

    public function maxLength(): int
    {
        return match ($this) {
            self::Member, self::Document => 50,
            self::Invoice => 255,
            self::SepaMandate => 35,
        };
    }

    public function table(): string
    {
        return match ($this) {
            self::Member => 'members',
            self::Invoice => 'invoices',
            self::SepaMandate => 'sepa_mandates',
            self::Document => 'documents',
        };
    }

    public function column(): string
    {
        return match ($this) {
            self::Member => 'member_number',
            self::Invoice => 'invoice_number',
            self::SepaMandate => 'mandate_reference',
            self::Document => 'document_number',
        };
    }
}
