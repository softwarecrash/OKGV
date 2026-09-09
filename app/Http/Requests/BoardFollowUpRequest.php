<?php

namespace App\Http\Requests;

use App\Models\BoardMeeting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoardFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BoardMeeting::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['required', 'date'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Aufgabe', 'description' => 'Beschreibung', 'due_at' => 'Fälligkeit', 'assigned_to' => 'Zuständigkeit'];
    }
}
