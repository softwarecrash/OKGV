<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = match (true) {
            $this->routeIs('tasks.start') => 'start',
            $this->routeIs('tasks.complete') => 'complete',
            $this->routeIs('tasks.cancel') => 'cancel',
            $this->routeIs('tasks.archive') => 'archive',
            $this->routeIs('tasks.restore') => 'restore',
            default => null,
        };

        return $ability !== null && $this->user()->can($ability, $this->route('task'));
    }

    public function rules(): array
    {
        return $this->routeIs('tasks.cancel') ? ['cancel_reason' => ['required', 'string', 'max:2000']] : [];
    }

    public function attributes(): array
    {
        return ['cancel_reason' => 'Grund für den Abbruch'];
    }
}
