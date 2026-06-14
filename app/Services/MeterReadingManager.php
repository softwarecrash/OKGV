<?php

namespace App\Services;

use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MeterReadingManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MeterReading
    {
        return DB::transaction(function () use ($data): MeterReading {
            $meter = Meter::query()->lockForUpdate()->findOrFail($data['meter_id']);
            $date = $data['reading_date'];
            $value = $data['reading_value'];

            if ($meter->readings()->whereDate('reading_date', $date)->exists()) {
                throw ValidationException::withMessages([
                    'reading_date' => 'Für dieses Datum existiert bereits ein Zählerstand.',
                ]);
            }

            if ($date < $meter->installed_at->toDateString()
                || ($meter->removed_at && $date > $meter->removed_at->toDateString())) {
                throw ValidationException::withMessages([
                    'reading_date' => 'Das Ablesedatum liegt außerhalb der Laufzeit des Zählers.',
                ]);
            }

            $previous = $meter->readings()
                ->whereDate('reading_date', '<', $date)
                ->with('corrections')
                ->latest('reading_date')
                ->first();
            $previousValue = $previous?->effective_reading_value ?? $meter->start_reading;

            if (bccomp($value, $previousValue, 4) < 0) {
                throw ValidationException::withMessages([
                    'reading_value' => 'Der Zählerstand darf nicht kleiner als der vorherige Stand sein.',
                ]);
            }

            $next = $meter->readings()
                ->whereDate('reading_date', '>', $date)
                ->with('corrections')
                ->oldest('reading_date')
                ->first();
            $nextValue = $next?->effective_reading_value ?? $meter->end_reading;

            if ($nextValue !== null && bccomp($value, $nextValue, 4) > 0) {
                throw ValidationException::withMessages([
                    'reading_value' => 'Der Zählerstand darf nicht größer als ein späterer Stand sein.',
                ]);
            }

            return MeterReading::create($data);
        });
    }
}
