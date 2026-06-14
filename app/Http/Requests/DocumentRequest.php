<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof Document
            ? $this->user()->can('update', $document)
            : $this->user()->can('create', Document::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['published' => $this->boolean('published')]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(DocumentType::class)],
            'visibility' => ['required', Rule::enum(DocumentVisibility::class)],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'parcel_id' => ['nullable', 'integer', 'exists:parcels,id'],
            'published' => ['required', 'boolean'],
            'file' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'file',
                'max:20480',
                'extensions:pdf,jpg,jpeg,png,webp,txt,docx,xlsx',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('visibility') === DocumentVisibility::Tenant->value
                    && ! $this->filled('member_id')
                    && ! $this->filled('parcel_id')) {
                    $validator->errors()->add(
                        'member_id',
                        'Für ein Pächterdokument muss ein Mitglied oder eine Parzelle ausgewählt werden.',
                    );
                }
            },
        ];
    }
}
