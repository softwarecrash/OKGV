<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum TaskRecurrence: string
{
    case None = 'none';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Keine', self::Weekly => 'Wöchentlich',
            self::Monthly => 'Monatlich', self::Yearly => 'Jährlich',
        };
    }

    public function date(CarbonInterface $anchor, int $occurrence): CarbonInterface
    {
        return match ($this) {
            self::Weekly => $anchor->copy()->addWeeks($occurrence),
            self::Monthly => $anchor->copy()->addMonthsNoOverflow($occurrence),
            self::Yearly => $anchor->copy()->addYearsNoOverflow($occurrence),
            self::None => $anchor->copy(),
        };
    }
}
