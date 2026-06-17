<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationRequestStatus;
use App\Http\Requests\TenantRegistrationRequest;
use App\Models\Parcel;
use App\Models\RegistrationRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantRegistrationController extends Controller
{
    public function create(): View
    {
        return view('tenant-registration.create');
    }

    public function store(TenantRegistrationRequest $request): RedirectResponse
    {
        $parcelNumber = $request->validated('parcel_number');
        $parcel = $parcelNumber === null
            ? null
            : Parcel::query()->where('parcel_number', $parcelNumber)->firstOrFail();

        $registrationRequest = RegistrationRequest::create([
            ...$request->safe()->only(['first_name', 'last_name', 'email', 'password']),
            'parcel_id' => $parcel?->id,
            'parcel_number' => $parcel?->parcel_number,
            'status' => RegistrationRequestStatus::Pending,
        ]);

        AuditLogger::log('tenant.registration.requested', subject: $registrationRequest, metadata: [
            'parcel_id' => $parcel?->id,
        ]);

        return redirect()->route('login')->with(
            'status',
            'Deine Registrierung wurde eingereicht. Du kannst dich anmelden, sobald der Vorstand oder ein Administrator die Anfrage bestätigt hat.',
        );
    }
}
