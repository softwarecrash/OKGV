<?php

namespace App\Http\Requests;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Models\BillingRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BillingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rate = $this->route('billing_rate');

        return $rate instanceof BillingRate
            ? $this->user()->can('update', $rate)
            : $this->user()->can('create', BillingRate::class);
    }

    public function rules(): array
    {
        $period = $this->route('billing_period');
        $rate = $this->route('billing_rate');

        return [
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('billing_rates', 'code')
                    ->where('billing_period_id', $period->id)
                    ->ignore($rate),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'calculation_type' => ['required', Rule::enum(BillingRateType::class)],
            'scope' => ['required', Rule::enum(BillingRateScope::class)],
            'amount' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
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
