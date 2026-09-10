<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PollActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = match ($this->route()->getName()) {
            'polls.publish' => 'publish', 'polls.close' => 'close', 'polls.archive' => 'archive', 'polls.restore' => 'restore', default => 'answer',
        };

        return $this->user()->can($ability, $this->route('poll'));
    }

    public function rules(): array
    {
        if ($this->routeIs('polls.answer')) {
            return ['confirmed' => ['accepted'], 'abstain' => ['sometimes', 'boolean'], 'option_ids' => ['sometimes', 'array', 'max:20'], 'option_ids.*' => ['required', 'integer', 'distinct']];
        }

        return ['confirmed' => ['accepted']];
    }

    public function messages(): array
    {
        return ['confirmed.accepted' => 'Bitte bestätige die beschriebene Aktion.', 'option_ids.*.distinct' => 'Bitte wähle jede Antwort nur einmal.'];
    }
}
