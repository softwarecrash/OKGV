<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationRequestStatus;
use App\Enums\UserRole;
use App\Http\Requests\TenantRegistrationRequest;
use App\Models\Parcel;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

        [$registrationRequest, $user] = DB::transaction(function () use ($request, $parcel): array {
            $validated = $request->validated();
            $fullName = "{$validated['first_name']} {$validated['last_name']}";
            $user = User::create([
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => UserRole::Tenant,
            ]);
            $registrationRequest = RegistrationRequest::create([
                ...$request->safe()->only(['first_name', 'last_name', 'email']),
                'user_id' => $user->id,
                'parcel_id' => $parcel?->id,
                'parcel_number' => $parcel?->parcel_number,
                'status' => RegistrationRequestStatus::Pending,
            ]);

            return [$registrationRequest, $user];
        });

        AuditLogger::log('tenant.registration.requested', subject: $registrationRequest, metadata: [
            'user_id' => $user->id,
            'parcel_id' => $parcel?->id,
        ]);

        $user->sendEmailVerificationNotification();

        return redirect()->route('login')->with(
            'status',
            'Dein Konto wurde angelegt und wartet auf Freigabe. Wenn die Bestätigungsmail ankommt, kannst du deine E-Mail-Adresse schon vorab bestätigen.',
        );
    }
}
