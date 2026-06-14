<?php

namespace App\Http\Requests;

use App\Models\ParcelTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ParcelTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tenancy = $this->route('parcel_tenant');

        return $tenancy instanceof ParcelTenant
            ? $this->user()->can('update', $tenancy)
            : $this->user()->can('create', ParcelTenant::class);
    }

    public function rules(): array
    {
        return [
            'parcel_id' => ['required', 'integer', 'exists:parcels,id'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_primary' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $query = ParcelTenant::query()
                    ->where('parcel_id', $this->integer('parcel_id'))
                    ->where('member_id', $this->integer('member_id'))
                    ->whereDate('starts_at', '<=', $this->input('ends_at') ?: '9999-12-31')
                    ->where(function ($query): void {
                        $query->whereNull('ends_at')
                            ->orWhereDate('ends_at', '>=', $this->date('starts_at'));
                    });

                if ($tenancy = $this->route('parcel_tenant')) {
                    $query->whereKeyNot($tenancy->getKey());
                }

                if ($query->exists()) {
                    $validator->errors()->add(
                        'starts_at',
                        'Für dieses Mitglied existiert auf der Parzelle bereits ein überschneidender Zeitraum.',
                    );
                }

                if (! $this->boolean('is_primary')) {
                    return;
                }

                $primaryQuery = ParcelTenant::query()
                    ->where('parcel_id', $this->integer('parcel_id'))
                    ->where('is_primary', true)
                    ->whereDate('starts_at', '<=', $this->input('ends_at') ?: '9999-12-31')
                    ->where(function ($query): void {
                        $query->whereNull('ends_at')
                            ->orWhereDate('ends_at', '>=', $this->date('starts_at'));
                    });

                if ($tenancy = $this->route('parcel_tenant')) {
                    $primaryQuery->whereKeyNot($tenancy->getKey());
                }

                if ($primaryQuery->exists()) {
                    $validator->errors()->add(
                        'is_primary',
                        'In diesem Zeitraum ist bereits ein Hauptpächter eingetragen.',
                    );
                }
            },
        ];
    }
}
