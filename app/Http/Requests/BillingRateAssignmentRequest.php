<?php

namespace App\Http\Requests;

use App\Enums\BillingRateScope;
use App\Models\BillingRateAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BillingRateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BillingRateAssignment::class);
    }

    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'integer', 'exists:members,id', 'required_without:parcel_id'],
            'parcel_id' => ['nullable', 'integer', 'exists:parcels,id', 'required_without:member_id'],
            'quantity' => ['required', 'numeric', 'decimal:0,4', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $rate = $this->route('billing_rate');

                if ($rate->scope !== BillingRateScope::Assignment) {
                    $validator->errors()->add(
                        'billing_rate',
                        'Nur Preise mit dem Geltungsbereich Zuordnung können zugewiesen werden.',
                    );
                }

                if (! $rate->billingPeriod->isMutable()) {
                    $validator->errors()->add(
                        'billing_rate',
                        'Zuordnungen können nur im Entwurf geändert werden.',
                    );
                }

                if ($this->filled('member_id') && $this->filled('parcel_id')) {
                    $validator->errors()->add(
                        'member_id',
                        'Eine Zuordnung darf nur ein Mitglied oder eine Parzelle betreffen.',
                    );
                }
            },
        ];
    }
}
