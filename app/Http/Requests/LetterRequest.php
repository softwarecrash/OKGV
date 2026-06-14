<?php

namespace App\Http\Requests;

use App\Models\Letter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Letter::class);
    }

    public function rules(): array
    {
        return [
            'member_id' => ['nullable', Rule::exists('members', 'id')],
            'recipient_name' => ['required_without:member_id', 'nullable', 'string', 'max:255'],
            'street' => ['required_without:member_id', 'nullable', 'string', 'max:255'],
            'zip' => ['required_without:member_id', 'nullable', 'string', 'max:20'],
            'city' => ['required_without:member_id', 'nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
        ];
    }
}
