<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BackupRestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    public function rules(): array
    {
        return [
            'backup' => [
                'required',
                'file',
                'max:1048576',
                'extensions:zip',
                'mimetypes:application/zip,application/x-zip-compressed',
            ],
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', Rule::in(['WIEDERHERSTELLEN'])],
        ];
    }
}
