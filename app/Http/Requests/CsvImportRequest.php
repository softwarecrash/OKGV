<?php

namespace App\Http\Requests;

use App\Enums\DataTransferType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageDataTransfer();
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::enum(DataTransferType::class)->only(array_values(array_filter(
                    DataTransferType::cases(),
                    fn (DataTransferType $type): bool => $type->importable(),
                ))),
            ],
            'file' => [
                'required',
                'file',
                'max:20480',
                'extensions:csv,txt',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
            ],
        ];
    }
}
