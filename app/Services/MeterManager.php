<?php

namespace App\Services;

use App\Enums\MeterStatus;
use App\Models\Meter;
use App\Models\Parcel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MeterManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Meter
    {
        return DB::transaction(function () use ($data): Meter {
            Parcel::query()->whereKey($data['parcel_id'])->lockForUpdate()->firstOrFail();
            $this->ensureNoActiveMeter($data['parcel_id'], $data['type']);

            return Meter::create($data + [
                'removed_at' => null,
                'end_reading' => null,
                'status' => MeterStatus::Active,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $replacement
     */
    public function replace(
        Meter $oldMeter,
        string $replacedAt,
        string $endReading,
        array $replacement,
    ): Meter {
        return DB::transaction(function () use (
            $oldMeter,
            $replacedAt,
            $endReading,
            $replacement,
        ): Meter {
            Parcel::query()->whereKey($oldMeter->parcel_id)->lockForUpdate()->firstOrFail();
            $oldMeter = Meter::query()->lockForUpdate()->findOrFail($oldMeter->id);

            if ($oldMeter->status !== MeterStatus::Active) {
                throw ValidationException::withMessages([
                    'meter' => 'Nur ein aktiver Zähler kann gewechselt werden.',
                ]);
            }

            if ($replacedAt < $oldMeter->installed_at->toDateString()) {
                throw ValidationException::withMessages([
                    'replaced_at' => 'Das Wechseldatum darf nicht vor dem Einbaudatum liegen.',
                ]);
            }

            if (bccomp($endReading, $oldMeter->start_reading, 4) < 0) {
                throw ValidationException::withMessages([
                    'end_reading' => 'Der Endstand darf nicht kleiner als der Startstand sein.',
                ]);
            }

            if ($oldMeter->readings()->whereDate('reading_date', '>', $replacedAt)->exists()) {
                throw ValidationException::withMessages([
                    'replaced_at' => 'Nach dem Wechseldatum existieren bereits Zählerstände.',
                ]);
            }

            $lastReading = $oldMeter->readings()
                ->whereDate('reading_date', '<=', $replacedAt)
                ->latest('reading_date')
                ->value('reading_value');

            if ($lastReading !== null && bccomp($endReading, $lastReading, 4) < 0) {
                throw ValidationException::withMessages([
                    'end_reading' => 'Der Endstand darf nicht kleiner als der letzte Zählerstand sein.',
                ]);
            }

            $oldMeter->update([
                'removed_at' => $replacedAt,
                'end_reading' => $endReading,
                'status' => MeterStatus::Replaced,
            ]);

            return Meter::create([
                ...$replacement,
                'parcel_id' => $oldMeter->parcel_id,
                'type' => $oldMeter->type,
                'installed_at' => $replacedAt,
                'removed_at' => null,
                'end_reading' => null,
                'status' => MeterStatus::Active,
            ]);
        });
    }

    private function ensureNoActiveMeter(int $parcelId, string $type): void
    {
        if (Meter::query()
            ->where('parcel_id', $parcelId)
            ->where('type', $type)
            ->where('status', MeterStatus::Active)
            ->exists()) {
            throw ValidationException::withMessages([
                'type' => 'Für diese Parzelle existiert bereits ein aktiver Zähler dieses Typs.',
            ]);
        }
    }
}
