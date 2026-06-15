<?php

namespace App\Http\Requests;

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ParcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $parcel = $this->route('parcel');

        return $parcel instanceof Parcel
            ? $this->user()->can('update', $parcel)
            : $this->user()->can('create', Parcel::class);
    }

    public function rules(): array
    {
        return [
            'parcel_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parcels', 'parcel_number')->ignore($this->route('parcel')),
            ],
            'area_sqm' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'status' => ['required', Rule::enum(ParcelStatus::class)],
            'location_description' => ['nullable', 'string', 'max:255'],
            'map_x' => ['nullable', 'integer', 'between:0,1199'],
            'map_y' => ['nullable', 'integer', 'between:0,799'],
            'map_width' => ['nullable', 'integer', 'between:20,1200'],
            'map_height' => ['nullable', 'integer', 'between:20,800'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $fields = ['map_x', 'map_y', 'map_width', 'map_height'];
                $filled = collect($fields)->filter(
                    fn (string $field): bool => $this->filled($field),
                );

                if ($filled->isNotEmpty() && $filled->count() !== count($fields)) {
                    $validator->errors()->add(
                        'map_x',
                        'Für die Platzierung müssen Position, Breite und Höhe vollständig angegeben werden.',
                    );

                    return;
                }

                if ($filled->isEmpty()) {
                    return;
                }

                if ((int) $this->input('map_x') + (int) $this->input('map_width') > 1200) {
                    $validator->errors()->add(
                        'map_width',
                        'Die Parzelle ragt rechts über den Lageplan hinaus. Verringere X-Position oder Breite.',
                    );
                }

                if ((int) $this->input('map_y') + (int) $this->input('map_height') > 800) {
                    $validator->errors()->add(
                        'map_height',
                        'Die Parzelle ragt unten über den Lageplan hinaus. Verringere Y-Position oder Höhe.',
                    );
                }
            },
        ];
    }
}
