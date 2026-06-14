<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParcelTenantRequest;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Services\AuditLogger;
use App\Services\ParcelTenancyManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParcelTenantController extends Controller
{
    public function __construct(private readonly ParcelTenancyManager $tenancyManager) {}

    public function create(Request $request): View
    {
        $this->authorize('create', ParcelTenant::class);

        return view('parcel-tenants.create', [
            'parcelTenant' => new ParcelTenant([
                'parcel_id' => $request->integer('parcel_id') ?: null,
                'member_id' => $request->integer('member_id') ?: null,
                'starts_at' => now(),
                'is_primary' => true,
            ]),
            'parcels' => Parcel::query()->orderBy('parcel_number')->get(),
            'members' => Member::query()
                ->where('status', '!=', 'archived')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function store(ParcelTenantRequest $request): RedirectResponse
    {
        $tenancy = $this->tenancyManager->save($request->validated());
        AuditLogger::log('parcel_tenant.created', $request->user(), $tenancy);

        return redirect()->route('parcels.show', $tenancy->parcel_id)
            ->with('status', 'Pächterzuordnung wurde angelegt.');
    }

    public function edit(ParcelTenant $parcelTenant): View
    {
        $this->authorize('update', $parcelTenant);

        return view('parcel-tenants.edit', [
            'parcelTenant' => $parcelTenant,
            'parcels' => Parcel::query()->orderBy('parcel_number')->get(),
            'members' => Member::query()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function update(
        ParcelTenantRequest $request,
        ParcelTenant $parcelTenant,
    ): RedirectResponse {
        $parcelTenant = $this->tenancyManager->save($request->validated(), $parcelTenant);
        AuditLogger::log('parcel_tenant.updated', $request->user(), $parcelTenant, [
            'changed_fields' => array_keys($parcelTenant->getChanges()),
        ]);

        return redirect()->route('parcels.show', $parcelTenant->parcel_id)
            ->with('status', 'Pächterzuordnung wurde aktualisiert.');
    }
}
