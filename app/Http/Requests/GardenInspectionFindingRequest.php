<?php

namespace App\Http\Requests;

use App\Models\GardenInspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GardenInspectionFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', GardenInspection::class) ?? false;
    }

    public function rules(): array
    {
        return ['parcel_id' => ['required', 'integer', Rule::exists('parcels', 'id')], 'category' => ['required', 'in:general,cabin,planting,paths,utilities,safety,other'], 'description' => ['required', 'string', 'max:5000'], 'due_at' => ['nullable', 'date'], 'responsible_member_id' => ['nullable', 'integer', Rule::exists('members', 'id')], 'task_id' => ['nullable', 'integer', Rule::exists('board_follow_ups', 'id')], 'internal_note' => ['nullable', 'string', 'max:5000'], 'photo' => ['nullable', 'file', 'max:10240', 'extensions:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp']];
    }
}
