<?php

namespace App\Http\Requests;

use App\Models\Meter;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\TenantTransition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TenantTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TenantTransition::class);
    }

    public function rules(): array
    {
        return [
            'parcel_id' => ['required', 'integer', Rule::exists('parcels', 'id')],
            'transfer_date' => [
                'required',
                'date',
                'before_or_equal:today',
                Rule::unique('tenant_transitions', 'transfer_date')
                    ->where('parcel_id', $this->integer('parcel_id')),
            ],
            'incoming_primary_member_id' => ['required', 'integer', Rule::exists('members', 'id')],
            'incoming_co_member_ids' => ['nullable', 'array', 'max:10'],
            'incoming_co_member_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('members', 'id'),
                Rule::notIn([$this->integer('incoming_primary_member_id')]),
            ],
            'meter_readings' => ['nullable', 'array'],
            'meter_readings.*' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => [
                'file',
                'max:8192',
                'extensions:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => [
                'file',
                'max:20480',
                'extensions:pdf,jpg,jpeg,png,webp,txt,docx,xlsx',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'notes' => ['nullable', 'string', 'max:10000'],
            'confirm_open_claims' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_open_claims.accepted' => 'Bitte bestätige, dass offene Forderungen bei den bisherigen Vertragsparteien verbleiben.',
            'meter_readings.*.required' => 'Für jeden aktiven Zähler ist ein Übergabestand erforderlich.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $parcel = Parcel::query()->find($this->integer('parcel_id'));
                $date = $this->date('transfer_date');

                if (! $parcel || ! $date) {
                    return;
                }

                $dayBefore = $date->copy()->subDay();
                $activePrimary = ParcelTenant::query()
                    ->where('parcel_id', $parcel->id)
                    ->where('is_primary', true)
                    ->activeOn($dayBefore)
                    ->first();

                if (! $activePrimary || $activePrimary->starts_at->gte($date)) {
                    $validator->errors()->add(
                        'transfer_date',
                        'Am Tag vor der Übergabe muss ein bisheriger Hauptpächter eingetragen sein.',
                    );
                }

                if ($activePrimary?->member_id === $this->integer('incoming_primary_member_id')) {
                    $validator->errors()->add(
                        'incoming_primary_member_id',
                        'Der neue Hauptpächter muss sich vom bisherigen Hauptpächter unterscheiden.',
                    );
                }

                $meterIds = Meter::query()
                    ->where('parcel_id', $parcel->id)
                    ->whereDate('installed_at', '<=', $date)
                    ->where(function ($query) use ($date): void {
                        $query->whereNull('removed_at')
                            ->orWhereDate('removed_at', '>=', $date);
                    })
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->sort()
                    ->values();
                $submittedMeterIds = collect(array_keys($this->input('meter_readings', [])))
                    ->map(fn ($id) => (string) $id)
                    ->sort()
                    ->values();

                if ($meterIds->all() !== $submittedMeterIds->all()) {
                    $validator->errors()->add(
                        'meter_readings',
                        'Bitte trage für jeden am Übergabetag vorhandenen Zähler genau einen Stand ein.',
                    );
                }
            },
        ];
    }
}
