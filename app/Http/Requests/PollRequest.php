<?php

namespace App\Http\Requests;

use App\Enums\PollAudience;
use App\Enums\PollResultsVisibility;
use App\Enums\PollType;
use App\Enums\UserRole;
use App\Models\Poll;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('poll') ? $this->user()->can('update', $this->route('poll')) : $this->user()->can('create', Poll::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('options'))) {
            $this->merge(['options' => array_values(array_filter($this->input('options'), fn ($option) => ! is_array($option) || collect($option)->contains(fn ($value) => $value !== null && $value !== '')))]);
        }
    }

    public function rules(): array
    {
        $dates = $this->input('type') === PollType::Dates->value;

        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::enum(PollType::class)],
            'multiple' => ['sometimes', 'boolean'],
            'audience' => ['required', Rule::enum(PollAudience::class)],
            'roles' => [Rule::requiredIf($this->input('audience') === PollAudience::Roles->value), 'array', 'max:6'],
            'roles.*' => ['required', Rule::enum(UserRole::class), 'distinct'],
            'results_visibility' => ['required', Rule::enum(PollResultsVisibility::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at', 'after:now'],
            'options' => ['required', 'array', 'min:2', 'max:20'],
            'options.*' => ['required', 'array:label,starts_at,ends_at'],
            'options.*.label' => ['required', 'string', 'max:180'],
            'options.*.starts_at' => [$dates ? 'required' : 'exclude', 'date', 'after:ends_at'],
            'options.*.ends_at' => [$dates ? 'nullable' : 'exclude', 'date', 'after:options.*.starts_at'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Titel', 'description' => 'Beschreibung', 'type' => 'Art', 'audience' => 'Zielgruppe', 'roles' => 'Rollen', 'results_visibility' => 'Ergebnissichtbarkeit', 'starts_at' => 'Beginn der Abstimmung', 'ends_at' => 'Ende der Abstimmung', 'options' => 'Antwortmöglichkeiten', 'options.*.label' => 'Beschriftung der Antwort', 'options.*.starts_at' => 'Terminbeginn', 'options.*.ends_at' => 'Terminende'];
    }

    public function messages(): array
    {
        return ['options.min' => 'Bitte gib mindestens zwei Antwortmöglichkeiten an.', 'options.max' => 'Es sind höchstens 20 Antwortmöglichkeiten erlaubt.', 'options.*.starts_at.after' => 'Termine müssen nach dem Ende der Abstimmung beginnen.', 'options.*.ends_at.after' => 'Das Terminende muss nach dem Terminbeginn liegen.', 'ends_at.after' => 'Das Abstimmungsende muss in der Zukunft und nach dem Beginn liegen.'];
    }
}
