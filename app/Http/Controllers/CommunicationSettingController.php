<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunicationSettingRequest;
use App\Models\CommunicationSetting;
use App\Services\AuditLogger;
use App\Services\CommunicationMailConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CommunicationSettingController extends Controller
{
    public function __construct(
        private readonly CommunicationMailConfigurator $configurator,
    ) {}

    public function edit(): View
    {
        $this->authorize('viewAny', CommunicationSetting::class);

        return view('communication-settings.edit', [
            'settings' => CommunicationSetting::current(),
        ]);
    }

    public function update(CommunicationSettingRequest $request): RedirectResponse
    {
        $settings = CommunicationSetting::current();
        $data = $request->validated();
        $clearCredentials = $data['clear_credentials'];
        unset($data['clear_credentials']);

        if ($clearCredentials) {
            $data['smtp_username'] = null;
            $data['smtp_password'] = null;
        } else {
            if (blank($data['smtp_username'])) {
                unset($data['smtp_username']);
            }

            if (blank($data['smtp_password'])) {
                unset($data['smtp_password']);
            }
        }

        $settings->update($data);

        AuditLogger::log('communication.settings.updated', $request->user(), $settings, [
            'changed_fields' => array_values(array_diff(
                array_keys($settings->getChanges()),
                ['smtp_username', 'smtp_password'],
            )),
            'credentials_changed' => $clearCredentials
                || $request->filled('smtp_username')
                || $request->filled('smtp_password'),
        ]);

        return back()->with('status', 'SMTP-Einstellungen wurden gespeichert.');
    }

    public function test(Request $request): RedirectResponse
    {
        $this->authorize('test', CommunicationSetting::class);
        $this->configurator->apply();

        Mail::mailer('okgv_smtp')->raw(
            'Der SMTP-Test war erfolgreich. Diese Nachricht wurde von der Kommunikationsverwaltung versendet.',
            fn ($message) => $message
                ->to($request->user()->email, $request->user()->name)
                ->subject('SMTP-Test '.config('app.name', 'OKGV')),
        );

        AuditLogger::log('communication.smtp.tested', $request->user());

        return back()->with('status', "Testmail wurde an {$request->user()->email} versendet.");
    }
}
