<?php

namespace App\Http\Requests;

use App\Enums\FeatureModule;
use App\Models\BoardMeeting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoardMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('meeting') ? $this->user()->can('update', $this->route('meeting')) : $this->user()->can('create', BoardMeeting::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'scheduled_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:180'],
            'attendees' => ['nullable', 'string', 'max:5000'],
            'minutes' => ['nullable', 'string', 'max:20000'],
            'document_ids' => [FeatureModule::Documents->enabled() ? 'nullable' : 'prohibited', 'array', 'max:20'],
            'document_ids.*' => ['integer', 'distinct', Rule::exists('documents', 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Titel', 'scheduled_at' => 'Sitzungstermin', 'location' => 'Ort', 'attendees' => 'Teilnehmer', 'minutes' => 'Protokoll', 'document_ids' => 'Dokumente', 'document_ids.*' => 'Dokument'];
    }
}
