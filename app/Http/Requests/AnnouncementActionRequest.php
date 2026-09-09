<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = match ($this->route()->getName()) {
            'announcements.publish' => 'publish',
            'announcements.archive' => 'archive',
            default => 'acknowledge',
        };

        return $this->user()->can($ability, $this->route('announcement'));
    }

    public function rules(): array
    {
        return $this->routeIs('announcements.acknowledge') ? ['confirmation' => ['accepted']] : [];
    }

    public function messages(): array
    {
        return ['confirmation.accepted' => 'Bitte bestätige, dass du die Mitteilung gelesen hast.'];
    }
}
