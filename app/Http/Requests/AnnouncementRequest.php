<?php

namespace App\Http\Requests;

use App\Enums\AnnouncementAudience;
use App\Enums\FeatureModule;
use App\Enums\UserRole;
use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('announcement')
            ? $this->user()->can('update', $this->route('announcement'))
            : $this->user()->can('create', Announcement::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'roles' => ['required_if:audience,roles', 'nullable', 'array', 'max:6'],
            'roles.*' => ['required', Rule::enum(UserRole::class), 'distinct'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_highlighted' => ['sometimes', 'boolean'],
            'requires_confirmation' => ['sometimes', 'boolean'],
            'document_ids' => [FeatureModule::Documents->enabled() ? 'nullable' : 'prohibited', 'array', 'max:10'],
            'document_ids.*' => ['integer', 'distinct', Rule::exists('documents', 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Titel', 'body' => 'Mitteilung', 'roles' => 'Zielrollen', 'roles.*' => 'Zielrolle', 'starts_at' => 'Beginn', 'ends_at' => 'Ende', 'document_ids' => 'Dokumente', 'document_ids.*' => 'Dokument'];
    }
}
