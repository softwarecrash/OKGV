<?php

namespace App\Services;

use App\Models\Parcel;
use App\Models\ParcelTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ParcelTenancyManager
{
    /**
     * Persist a tenancy while serializing changes for the affected parcels.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?ParcelTenant $tenancy = null): ParcelTenant
    {
        $data += [
            'ends_at' => null,
            'is_primary' => false,
            'notes' => null,
        ];

        return DB::transaction(function () use ($data, $tenancy): ParcelTenant {
            $parcelIds = array_values(array_unique(array_filter([
                $tenancy?->parcel_id,
                $data['parcel_id'],
            ])));

            Parcel::query()
                ->whereKey($parcelIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $this->ensureNoConflict($data, $tenancy);

            $tenancy ??= new ParcelTenant;
            $tenancy->fill($data)->save();

            return $tenancy;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureNoConflict(array $data, ?ParcelTenant $tenancy): void
    {
        $overlap = fn ($query) => $query
            ->whereDate('starts_at', '<=', $data['ends_at'] ?: '9999-12-31')
            ->where(function ($query) use ($data): void {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $data['starts_at']);
            })
            ->when($tenancy, fn ($query) => $query->whereKeyNot($tenancy->getKey()));

        $sameMemberExists = $overlap(
            ParcelTenant::query()
                ->where('parcel_id', $data['parcel_id'])
                ->where('member_id', $data['member_id']),
        )->exists();

        if ($sameMemberExists) {
            throw ValidationException::withMessages([
                'starts_at' => 'Für dieses Mitglied existiert auf der Parzelle bereits ein überschneidender Zeitraum.',
            ]);
        }

        if (! $data['is_primary']) {
            return;
        }

        $primaryExists = $overlap(
            ParcelTenant::query()
                ->where('parcel_id', $data['parcel_id'])
                ->where('is_primary', true),
        )->exists();

        if ($primaryExists) {
            throw ValidationException::withMessages([
                'is_primary' => 'In diesem Zeitraum ist bereits ein Hauptpächter eingetragen.',
            ]);
        }
    }
}
