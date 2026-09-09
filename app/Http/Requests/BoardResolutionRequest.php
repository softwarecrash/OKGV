<?php

namespace App\Http\Requests;

use App\Enums\BoardResolutionResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoardResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('meeting'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
            'result' => ['required', Rule::enum(BoardResolutionResult::class)],
            'board_agenda_item_id' => ['nullable', 'integer', Rule::exists('board_agenda_items', 'id')->where('board_meeting_id', $this->route('meeting')->id)],
            'votes_for' => ['nullable', 'required_with:votes_against,votes_abstained', 'integer', 'min:0', 'max:10000'],
            'votes_against' => ['nullable', 'required_with:votes_for,votes_abstained', 'integer', 'min:0', 'max:10000'],
            'votes_abstained' => ['nullable', 'required_with:votes_for,votes_against', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Beschlusstitel', 'body' => 'Beschlusstext', 'result' => 'Ergebnis', 'board_agenda_item_id' => 'Tagesordnungspunkt', 'votes_for' => 'Ja-Stimmen', 'votes_against' => 'Nein-Stimmen', 'votes_abstained' => 'Enthaltungen'];
    }

    public function messages(): array
    {
        return ['required_with' => 'Bitte alle drei Stimmenzahlen angeben (auch 0) oder alle drei Felder leer lassen.'];
    }
}
