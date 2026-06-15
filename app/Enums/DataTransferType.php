<?php

namespace App\Enums;

enum DataTransferType: string
{
    case Members = 'members';
    case Parcels = 'parcels';
    case Meters = 'meters';
    case MeterReadings = 'meter_readings';
    case Invoices = 'invoices';

    public function label(): string
    {
        return match ($this) {
            self::Members => 'Mitglieder',
            self::Parcels => 'Parzellen',
            self::Meters => 'Zähler',
            self::MeterReadings => 'Zählerstände',
            self::Invoices => 'Rechnungen',
        };
    }

    public function importable(): bool
    {
        return $this !== self::Invoices;
    }

    public function requiresMeters(): bool
    {
        return in_array($this, [self::Meters, self::MeterReadings], true);
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return match ($this) {
            self::Members => [
                'member_number', 'first_name', 'last_name', 'street', 'zip',
                'city', 'phone', 'mobile', 'email', 'joined_at', 'left_at',
                'status', 'notes',
            ],
            self::Parcels => [
                'parcel_number', 'area_sqm', 'status', 'location_description',
                'notes',
            ],
            self::Meters => [
                'parcel_number', 'type', 'meter_number', 'installed_at',
                'removed_at', 'start_reading', 'end_reading', 'status', 'notes',
            ],
            self::MeterReadings => [
                'meter_number', 'reading_value', 'reading_date', 'source', 'notes',
            ],
            self::Invoices => [
                'invoice_number', 'billing_period', 'status', 'payment_status',
                'issued_at', 'due_at', 'paid_at', 'total_amount', 'recipients',
                'item_code', 'item_description', 'parcel_number', 'quantity',
                'unit_price', 'item_total',
            ],
        };
    }

    /**
     * @return list<string>
     */
    public function example(): array
    {
        return match ($this) {
            self::Members => [
                'M-1001', 'Erika', 'Muster', 'Gartenweg 1', '99423', 'Weimar',
                '03643 12345', '0170 1234567', 'erika@example.de', '2024-01-01',
                '', 'active', '',
            ],
            self::Parcels => ['A-01', '320.50', 'free', 'Nordweg', ''],
            self::Meters => [
                'A-01', 'water', 'W-10001', '2024-01-01', '', '0.0000', '',
                'active', '',
            ],
            self::MeterReadings => [
                'W-10001', '12.5000', '2024-12-31', 'import', '',
            ],
            self::Invoices => [],
        };
    }
}
