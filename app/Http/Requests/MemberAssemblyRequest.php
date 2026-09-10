<?php

namespace App\Http\Requests;

use App\Enums\MemberAssemblyMode;
use App\Models\MemberAssembly;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberAssemblyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $resolutions = array_values(array_filter($this->input('resolutions', []), fn ($resolution) => filled($resolution['title'] ?? null) || filled($resolution['body'] ?? null)));
        $this->merge(['resolutions' => $resolutions]);
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', MemberAssembly::class);
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:180'], 'mode' => ['required', Rule::enum(MemberAssemblyMode::class)], 'scheduled_at' => ['required', 'date', 'after:now'], 'location' => ['nullable', 'string', 'max:180'], 'electronic_rights_notice' => ['nullable', 'string', 'max:5000'], 'virtual_authorization_reference' => ['nullable', 'string', 'max:500'], 'agenda' => ['required', 'string', 'max:20000'], 'resolutions' => ['required', 'array', 'min:1', 'max:30'], 'resolutions.*.title' => ['required', 'string', 'max:180'], 'resolutions.*.body' => ['required', 'string', 'max:5000']];
    }
}
