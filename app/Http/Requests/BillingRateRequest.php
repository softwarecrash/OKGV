<?php

namespace App\Http\Requests;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\BillingSettlementType;
use App\Models\BillingRate;
use App\Models\BillingRateTemplate;
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
            'billing_rate_template_id' => [
                'nullable',
                'integer',
                Rule::exists('billing_rate_templates', 'id')
                    ->where('is_active', true),
            ],
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
            'settlement_type' => ['required', Rule::enum(BillingSettlementType::class)],
            'service_starts_at' => ['required', 'date'],
            'service_ends_at' => ['required', 'date', 'after_or_equal:service_starts_at'],
            'amount' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'prorate' => ['sometimes', 'boolean'],
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

                if (in_array($type, [
                    BillingRateType::PerKilowattHour,
                    BillingRateType::PerCubicMeter,
                ], true) && $this->boolean('prorate')) {
                    $validator->errors()->add(
                        'prorate',
                        'Verbrauchskosten werden bereits über den tatsächlichen Verbrauch im Leistungszeitraum abgegrenzt und nicht zusätzlich zeitanteilig gekürzt.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $template = BillingRateTemplate::query()
            ->whereKey($this->integer('billing_rate_template_id'))
            ->where('is_active', true)
            ->first();
        $code = strtoupper(trim((string) $this->input('code')));

        if ($template) {
            $code = $template->code;
        }

        $this->merge([
            'billing_rate_template_id' => $template?->id,
            'code' => preg_replace('/\s+/', '_', $code),
            'name' => $template?->name ?? $this->input('name'),
            'description' => $template?->description ?? $this->input('description'),
            'calculation_type' => $template?->calculation_type->value
                ?? $this->input('calculation_type'),
            'scope' => $template?->scope->value ?? $this->input('scope'),
            'settlement_type' => $template?->settlement_type->value
                ?? $this->input('settlement_type', BillingSettlementType::Arrears->value),
            'service_starts_at' => $this->input(
                'service_starts_at',
                $this->route('billing_period')?->starts_at?->toDateString(),
            ),
            'service_ends_at' => $this->input(
                'service_ends_at',
                $this->route('billing_period')?->ends_at?->toDateString(),
            ),
            'prorate' => $template
                ? $template->prorate
                : $this->boolean('prorate'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
