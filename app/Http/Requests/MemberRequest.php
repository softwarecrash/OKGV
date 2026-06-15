<?php

namespace App\Http\Requests;

use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');

        return $member instanceof Member
            ? $this->user()->can('update', $member)
            : $this->user()->can('create', Member::class);
    }

    public function rules(): array
    {
        $member = $this->route('member');

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('members', 'user_id')->ignore($member),
            ],
            'member_number' => [
                Rule::requiredIf($member instanceof Member),
                'nullable',
                'string',
                'max:50',
                Rule::unique('members', 'member_number')->ignore($member),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:10'],
            'city' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'joined_at' => ['required', 'date'],
            'left_at' => ['nullable', 'date', 'after_or_equal:joined_at'],
            'status' => ['required', Rule::enum(MemberStatus::class)],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
