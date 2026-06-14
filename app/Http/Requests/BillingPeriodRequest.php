<?php

namespace App\Http\Requests;

use App\Models\BillingPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BillingPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $period = $this->route('billing_period');

        return $period instanceof BillingPeriod
            ? $this->user()->can('update', $period)
            : $this->user()->can('create', BillingPeriod::class);
    }

    public function rules(): array
    {
        $period = $this->route('billing_period');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('billing_periods', 'name')->ignore($period),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'due_at' => ['required', 'date', 'after:ends_at'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $query = BillingPeriod::query()
                    ->whereDate('starts_at', '<=', $this->date('ends_at'))
                    ->whereDate('ends_at', '>=', $this->date('starts_at'));

                if ($period = $this->route('billing_period')) {
                    $query->whereKeyNot($period->id);
                }

                if ($query->exists()) {
                    $validator->errors()->add(
                        'starts_at',
                        'Der Zeitraum überschneidet sich mit einer bestehenden Abrechnungsperiode.',
                    );
                }
            },
        ];
    }
}
