<?php

namespace App\Services;

use App\Models\CommunicationSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class CommunicationMailConfigurator
{
    public function apply(): CommunicationSetting
    {
        if (config('demo.enabled')) {
            throw ValidationException::withMessages([
                'smtp_enabled' => 'Der Mailversand ist im Demo-Modus deaktiviert.',
            ]);
        }

        $settings = CommunicationSetting::current();

        if (! $settings->smtp_enabled) {
            throw ValidationException::withMessages([
                'smtp_enabled' => 'Der Mailversand ist noch nicht aktiviert.',
            ]);
        }

        if ($settings->mailer_transport === 'sendmail') {
            $mailerConfig = [
                'transport' => 'sendmail',
                'path' => $settings->sendmail_path ?: config('mail.mailers.sendmail.path', '/usr/sbin/sendmail -bs -i'),
            ];
        } else {
            $mailerConfig = [
                'transport' => 'smtp',
                'host' => $settings->smtp_host,
                'port' => $settings->smtp_port,
                'username' => $settings->smtp_username,
                'password' => $settings->smtp_password,
                'timeout' => 15,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ];

            if ($settings->smtp_scheme === 'none') {
                $mailerConfig['scheme'] = 'smtp';
                $mailerConfig['auto_tls'] = false;
            } else {
                $mailerConfig['scheme'] = $settings->smtp_scheme;
            }
        }

        config([
            'mail.mailers.okgv_smtp' => $mailerConfig,
            'mail.from.address' => $settings->from_address,
            'mail.from.name' => $settings->from_name,
        ]);

        Mail::purge('okgv_smtp');

        return $settings;
    }
}
