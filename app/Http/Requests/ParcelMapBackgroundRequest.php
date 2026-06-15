<?php

namespace App\Http\Requests;

use App\Models\Parcel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ParcelMapBackgroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageMap', Parcel::class);
    }

    public function rules(): array
    {
        return [
            'background' => [
                'required',
                'file',
                'max:15360',
                'extensions:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
            'source' => ['required', 'string', 'max:255'],
            'rights_confirmed' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'background' => 'Hintergrundbild',
            'source' => 'Quelle und Nutzungsrecht',
            'rights_confirmed' => 'Bestätigung des Nutzungsrechts',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $background = $this->file('background');

                if (! $background?->isValid() || $validator->errors()->has('background')) {
                    return;
                }

                $dimensions = @getimagesize($background->getPathname());

                if ($dimensions === false) {
                    $validator->errors()->add(
                        'background',
                        'Das Hintergrundbild konnte nicht gelesen werden.',
                    );

                    return;
                }

                [$width, $height] = $dimensions;

                if ($width < 400 || $height < 300 || $width > 12000 || $height > 12000) {
                    $validator->errors()->add(
                        'background',
                        'Das Hintergrundbild muss zwischen 400 x 300 und 12000 x 12000 Pixel groß sein.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source' => trim((string) $this->input('source')),
            'rights_confirmed' => $this->boolean('rights_confirmed'),
        ]);
    }
}
