<?php

namespace App\Http\Requests;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Models\Meter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meter = $this->route('meter');

        return $meter instanceof Meter
            ? $this->user()->can('update', $meter)
            : $this->user()->can('create', Meter::class);
    }

    public function rules(): array
    {
        $meter = $this->route('meter');

        if ($meter instanceof Meter) {
            $rules = [
                'meter_number' => [
                    'required', 'string', 'max:100',
                    Rule::unique('meters', 'meter_number')->ignore($meter),
                ],
                'notes' => ['nullable', 'string', 'max:10000'],
            ];

            if (in_array($meter->status, [MeterStatus::Active, MeterStatus::Defective], true)) {
                $rules['status'] = ['required', Rule::in([
                    MeterStatus::Active->value,
                    MeterStatus::Defective->value,
                ])];
            }

            return $rules;
        }

        return [
            'parcel_id' => ['required', 'integer', 'exists:parcels,id'],
            'type' => ['required', Rule::enum(MeterType::class)],
            'meter_number' => ['required', 'string', 'max:100', 'unique:meters,meter_number'],
            'installed_at' => ['required', 'date'],
            'start_reading' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $meter = $this->route('meter');

                if (! $meter instanceof Meter
                    || $this->input('status') !== MeterStatus::Active->value
                    || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $activeMeterExists = Meter::query()
                    ->where('parcel_id', $meter->parcel_id)
                    ->where('type', $meter->type)
                    ->where('status', MeterStatus::Active)
                    ->whereKeyNot($meter->id)
                    ->exists();

                if ($activeMeterExists) {
                    $validator->errors()->add(
                        'status',
                        'Für diese Parzelle existiert bereits ein aktiver Zähler dieses Typs.',
                    );
                }
            },
        ];
    }
}
