<?php

namespace App\Http\Requests;

use App\Enums\TaskRecurrence;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('task') ? $this->user()->can('update', $this->route('task')) : $this->user()->can('create', Task::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['required', 'date_format:Y-m-d', 'before:9999-01-01'],
            'remind_at' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:due_at'],
            'recurrence' => ['required', Rule::enum(TaskRecurrence::class)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'member_id' => ['sometimes', 'nullable', 'integer', Rule::exists('members', 'id')],
            'parcel_id' => ['sometimes', 'nullable', 'integer', Rule::exists('parcels', 'id')],
            'document_id' => ['sometimes', 'nullable', 'integer', Rule::exists('documents', 'id')],
            'board_resolution_id' => [$this->route('task') ? 'prohibited' : 'nullable', 'integer', Rule::exists('board_resolutions', 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Aufgabe', 'description' => 'Beschreibung', 'due_at' => 'Fälligkeit', 'remind_at' => 'Wiedervorlage', 'recurrence' => 'Wiederholung', 'assigned_to' => 'Zuständigkeit', 'member_id' => 'Mitglied', 'parcel_id' => 'Parzelle', 'document_id' => 'Dokument', 'board_resolution_id' => 'Beschluss'];
    }

    public function messages(): array
    {
        return ['remind_at.before_or_equal' => 'Die Wiedervorlage muss am oder vor dem Fälligkeitstag liegen.', 'board_resolution_id.prohibited' => 'Die ursprüngliche Beschlusszuordnung bleibt erhalten.'];
    }
}
