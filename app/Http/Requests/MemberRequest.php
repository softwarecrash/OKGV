<?php

namespace App\Http\Requests;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\User;
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
        $currentUserId = $member instanceof Member ? $member->user_id : null;

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('members', 'user_id')->ignore($member),
                function (string $attribute, mixed $value, \Closure $fail) use ($member, $currentUserId): void {
                    if (! $member instanceof Member || (int) $value === $currentUserId) {
                        return;
                    }

                    $account = User::query()->find($value);

                    if ($account && strcasecmp($account->email, (string) $this->input('email')) !== 0) {
                        $fail('Das ausgewählte Benutzerkonto muss dieselbe E-Mail-Adresse wie die Mitgliedsstammdaten verwenden. Korrigiere zuerst die Login- oder Kontakt-E-Mail.');
                    }
                },
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
