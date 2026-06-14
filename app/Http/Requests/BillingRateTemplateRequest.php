<?php

namespace App\Http\Requests;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Models\BillingRateTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BillingRateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('billing_rate_template');

        return $template instanceof BillingRateTemplate
            ? $this->user()->can('update', $template)
            : $this->user()->can('create', BillingRateTemplate::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('billing_rate_templates', 'code')
                    ->ignore($this->route('billing_rate_template')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'calculation_type' => ['required', Rule::enum(BillingRateType::class)],
            'scope' => ['required', Rule::enum(BillingRateScope::class)],
            'default_amount' => ['nullable', 'numeric', 'decimal:0,4', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $type = BillingRateType::from($this->string('calculation_type')->toString());
                $scope = BillingRateScope::from($this->string('scope')->toString());

                if (in_array($type, [
                    BillingRateType::PerSquareMeter,
                    BillingRateType::PerKilowattHour,
                    BillingRateType::PerCubicMeter,
                ], true) && $scope !== BillingRateScope::Parcel) {
                    $validator->errors()->add(
                        'scope',
                        'Flächen- und Verbrauchspreise müssen je Parzelle gelten.',
                    );
                }

                if ($scope === BillingRateScope::Assignment
                    && ! in_array($type, [BillingRateType::Fixed, BillingRateType::Manual], true)) {
                    $validator->errors()->add(
                        'calculation_type',
                        'Zugeordnete Kosten müssen Festbeträge oder manuelle Positionen sein.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code')));

        $this->merge([
            'code' => preg_replace('/\s+/', '_', $code),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
