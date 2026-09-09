<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BoardActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return match (true) {
            $this->routeIs('board-meetings.finalize') => $this->user()->can('finalize', $this->route('meeting')),
            $this->routeIs('board-meetings.archive') => $this->user()->can('archive', $this->route('meeting')),
            $this->routeIs('board-meetings.unarchive') => $this->user()->can('unarchive', $this->route('meeting')),
            $this->routeIs('board-follow-ups.complete') => $this->user()->can('complete', $this->route('task')),
            default => false,
        };
    }

    public function rules(): array
    {
        return $this->routeIs('board-meetings.finalize') ? ['confirmed' => ['accepted']] : [];
    }

    public function messages(): array
    {
        return ['confirmed.accepted' => 'Bitte bestätige, dass du das Protokoll geprüft hast und unveränderbar abschließen möchtest.'];
    }
}
