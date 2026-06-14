<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunicationSettingRequest;
use App\Http\Requests\SmtpTestRequest;
use App\Mail\SmtpTestMessage;
use App\Models\CommunicationSetting;
use App\Services\AuditLogger;
use App\Services\CommunicationMailConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class CommunicationSettingController extends Controller
{
    public function __construct(
        private readonly CommunicationMailConfigurator $configurator,
    ) {}

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

        return redirect()
            ->route('application-settings.edit', ['section' => 'smtp'])
            ->with('status', 'SMTP-Einstellungen wurden gespeichert.');
    }

    public function test(SmtpTestRequest $request): RedirectResponse
    {
        $this->configurator->apply();
        $recipient = $request->validated('test_email');

        Mail::mailer('okgv_smtp')
            ->to($recipient)
            ->send(new SmtpTestMessage);

        AuditLogger::log('communication.smtp.tested', $request->user(), metadata: [
            'recipient' => $recipient,
        ]);

        return redirect()
            ->route('application-settings.edit', ['section' => 'smtp'])
            ->with('status', "Testmail wurde an {$recipient} versendet.");
    }
}
