<?php

namespace App\Http\Requests;

use App\Enums\NumberSequenceType;
use App\Models\NumberSequence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class NumberSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', NumberSequence::class);
    }

    protected function prepareForValidation(): void
    {
        $sequences = collect($this->input('sequences', []))
            ->map(fn (mixed $sequence): array => [
                ...(array) $sequence,
                'format' => mb_strtoupper(preg_replace(
                    '/\s+/',
                    '-',
                    trim((string) data_get($sequence, 'format')),
                )),
                'reset_yearly' => filter_var(
                    data_get($sequence, 'reset_yearly'),
                    FILTER_VALIDATE_BOOLEAN,
                ),
            ])
            ->all();

        $this->merge(['sequences' => $sequences]);
    }

    public function rules(): array
    {
        return [
            'sequences' => ['required', 'array'],
            'sequences.*.format' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9{}_.+?\/\-:()]+$/',
            ],
            'sequences.*.padding' => ['required', 'integer', 'between:1,12'],
            'sequences.*.next_value' => ['required', 'integer', 'between:1,999999999999'],
            'sequences.*.reset_yearly' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (NumberSequenceType::cases() as $type) {
                    $format = (string) $this->input("sequences.{$type->value}.format");

                    if (! str_contains($format, '{NUMMER}')) {
                        $validator->errors()->add(
                            "sequences.{$type->value}.format",
                            'Das Format muss den Platzhalter {NUMMER} enthalten.',
                        );
                    }

                    $literalFormat = str_replace(
                        ['{JAHR}', '{NUMMER}'],
                        '',
                        $format,
                    );

                    if (str_contains($literalFormat, '{')
                        || str_contains($literalFormat, '}')) {
                        $validator->errors()->add(
                            "sequences.{$type->value}.format",
                            'Erlaubte Platzhalter sind ausschließlich {JAHR} und {NUMMER}.',
                        );
                    }

                    if ($this->boolean("sequences.{$type->value}.reset_yearly")
                        && ! str_contains($format, '{JAHR}')) {
                        $validator->errors()->add(
                            "sequences.{$type->value}.format",
                            'Bei jährlichem Neustart muss das Format {JAHR} enthalten, damit keine Doppelnummern entstehen.',
                        );
                    }

                    $padding = (int) $this->input("sequences.{$type->value}.padding");
                    $nextValue = (string) $this->input("sequences.{$type->value}.next_value");
                    $preview = str_replace(
                        ['{JAHR}', '{NUMMER}'],
                        [
                            (string) now()->year,
                            str_repeat('9', max(1, $padding, strlen($nextValue))),
                        ],
                        $format,
                    );

                    if (mb_strlen($preview) > $type->maxLength()) {
                        $validator->errors()->add(
                            "sequences.{$type->value}.format",
                            "Das Ergebnis darf höchstens {$type->maxLength()} Zeichen lang sein.",
                        );
                    }
                }
            },
        ];
    }
}
