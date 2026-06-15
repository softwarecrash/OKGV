<?php

namespace App\Http\Requests;

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
