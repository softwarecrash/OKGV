<?php

namespace App\Http\Requests;

use App\Models\ApplicationSetting;
use App\Models\Parcel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ParcelMapPolygonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $parcel = $this->route('parcel');

        return $parcel instanceof Parcel
            && $this->user()->can('update', $parcel);
    }

    public function rules(): array
    {
        return [
            'remove_polygon' => ['required', 'boolean'],
            'polygon' => [
                Rule::excludeIf($this->boolean('remove_polygon')),
                Rule::requiredIf(! $this->boolean('remove_polygon')),
                'nullable',
                'array',
                'min:3',
                'max:100',
            ],
            'polygon.*.x' => ['required', 'numeric', 'min:0'],
            'polygon.*.y' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('remove_polygon')) {
                    return;
                }

                $settings = ApplicationSetting::current();

                foreach ((array) $this->input('polygon') as $index => $point) {
                    if ((float) data_get($point, 'x') > $settings->map_background_width) {
                        $validator->errors()->add(
                            "polygon.{$index}.x",
                            'Ein Punkt liegt rechts außerhalb des Hintergrundbilds.',
                        );
                    }

                    if ((float) data_get($point, 'y') > $settings->map_background_height) {
                        $validator->errors()->add(
                            "polygon.{$index}.y",
                            'Ein Punkt liegt unterhalb des Hintergrundbilds.',
                        );
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $polygon = json_decode((string) $this->input('polygon'), true);

        $this->merge([
            'remove_polygon' => $this->boolean('remove_polygon'),
            'polygon' => is_array($polygon) ? $polygon : null,
        ]);
    }
}
