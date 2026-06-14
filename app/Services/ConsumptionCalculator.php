<?php

namespace App\Services;

use App\Models\Meter;
use Carbon\CarbonInterface;

final class ConsumptionCalculator
{
    public function forMeter(Meter $meter, CarbonInterface $from, CarbonInterface $to): string
    {
        $segmentStart = $from->max($meter->installed_at);
        $segmentEnd = $meter->removed_at ? $to->min($meter->removed_at) : $to;

        if ($segmentStart->gt($segmentEnd)) {
            return '0.0000';
        }

        $startReading = $meter->readings()
            ->whereDate('reading_date', '<=', $segmentStart)
            ->latest('reading_date')
            ->value('reading_value') ?? $meter->start_reading;

        $endReading = $meter->readings()
            ->whereDate('reading_date', '<=', $segmentEnd)
            ->latest('reading_date')
            ->value('reading_value');

        if ($endReading === null && $meter->removed_at?->lte($to)) {
            $endReading = $meter->end_reading;
        }

        if ($endReading === null || bccomp($endReading, $startReading, 4) < 0) {
            return '0.0000';
        }

        return bcsub($endReading, $startReading, 4);
    }

    public function forParcel(
        int $parcelId,
        string $type,
        CarbonInterface $from,
        CarbonInterface $to,
    ): string {
        $total = '0.0000';

        Meter::query()
            ->where('parcel_id', $parcelId)
            ->where('type', $type)
            ->whereDate('installed_at', '<=', $to)
            ->where(function ($query) use ($from): void {
                $query->whereNull('removed_at')->orWhereDate('removed_at', '>=', $from);
            })
            ->with('readings')
            ->each(function (Meter $meter) use (&$total, $from, $to): void {
                $total = bcadd($total, $this->forMeter($meter, $from, $to), 4);
            });

        return $total;
    }
}
