<?php

namespace App\Http\Requests;

use App\Models\GardenInspection;
use Illuminate\Foundation\Http\FormRequest;

class GardenInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', GardenInspection::class);
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:180'], 'inspected_at' => ['required', 'date'], 'inspectors' => ['required', 'string', 'max:2000'], 'instructions' => ['nullable', 'string', 'max:5000']];
    }
}
