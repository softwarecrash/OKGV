<?php

namespace App\Services;

use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MeterReadingCorrection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MeterReadingCorrectionManager
{
    public function create(
        MeterReading $reading,
        string $correctedValue,
        string $reason,
        User $actor,
    ): MeterReadingCorrection {
        return DB::transaction(function () use (
            $reading,
            $correctedValue,
            $reason,
            $actor,
        ): MeterReadingCorrection {
            if (! $actor->canCorrectMeterReadings()) {
                throw ValidationException::withMessages([
                    'permission' => 'Für dieses Konto ist keine Zählerstandkorrektur freigeschaltet.',
                ]);
            }

            $reading = MeterReading::query()
                ->with('corrections')
                ->lockForUpdate()
                ->findOrFail($reading->id);
            Meter::query()->whereKey($reading->meter_id)->lockForUpdate()->firstOrFail();

            $previous = MeterReading::query()
                ->where('meter_id', $reading->meter_id)
                ->whereDate('reading_date', '<', $reading->reading_date)
                ->with('corrections')
                ->latest('reading_date')
                ->first();
            $previousValue = $previous?->effective_reading_value
                ?? $reading->meter->start_reading;

            if (bccomp($correctedValue, $previousValue, 4) < 0) {
                throw ValidationException::withMessages([
                    'corrected_value' => 'Der korrigierte Stand darf nicht kleiner als der vorherige wirksame Stand sein.',
                ]);
            }

            $next = MeterReading::query()
                ->where('meter_id', $reading->meter_id)
                ->whereDate('reading_date', '>', $reading->reading_date)
                ->with('corrections')
                ->oldest('reading_date')
                ->first();
            $nextValue = $next?->effective_reading_value ?? $reading->meter->end_reading;

            if ($nextValue !== null && bccomp($correctedValue, $nextValue, 4) > 0) {
                throw ValidationException::withMessages([
                    'corrected_value' => 'Der korrigierte Stand darf nicht größer als der nächste wirksame Stand sein.',
                ]);
            }

            $correction = $reading->corrections()->create([
                'corrected_value' => $correctedValue,
                'reason' => $reason,
                'corrected_by' => $actor->id,
            ]);

            AuditLogger::log(
                action: 'meter_reading.corrected',
                actor: $actor,
                subject: $reading,
                metadata: [
                    'correction_id' => $correction->id,
                    'original_value' => $reading->reading_value,
                    'previous_effective_value' => $reading->effective_reading_value,
                    'corrected_value' => $correctedValue,
                    'reason' => $reason,
                ],
            );

            return $correction;
        });
    }
}
