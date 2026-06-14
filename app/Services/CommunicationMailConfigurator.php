<?php

namespace App\Services;

use App\Models\CommunicationSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class CommunicationMailConfigurator
{
    public function apply(): CommunicationSetting
    {
        $settings = CommunicationSetting::current();

        if (! $settings->smtp_enabled) {
            throw ValidationException::withMessages([
                'smtp_enabled' => 'Der SMTP-Versand ist noch nicht aktiviert.',
            ]);
        }

        config([
            'mail.mailers.okgv_smtp' => [
                'transport' => 'smtp',
                'scheme' => $settings->smtp_scheme,
                'host' => $settings->smtp_host,
                'port' => $settings->smtp_port,
                'username' => $settings->smtp_username,
                'password' => $settings->smtp_password,
                'timeout' => 15,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ],
            'mail.from.address' => $settings->from_address,
            'mail.from.name' => $settings->from_name,
        ]);

        Mail::purge('okgv_smtp');

        return $settings;
    }
}
