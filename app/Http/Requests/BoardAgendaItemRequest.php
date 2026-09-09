<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BoardAgendaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('meeting'));
    }

    public function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'min:1', 'max:999'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'minutes' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function attributes(): array
    {
        return ['position' => 'Reihenfolge', 'title' => 'Thema', 'description' => 'Beschreibung', 'minutes' => 'Protokoll zum Thema'];
    }
}
