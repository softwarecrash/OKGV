<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'smtp_enabled',
    'mailer_transport',
    'smtp_scheme',
    'smtp_host',
    'smtp_port',
    'smtp_username',
    'smtp_password',
    'sendmail_path',
    'from_address',
    'from_name',
])]
class CommunicationSetting extends Model
{
    protected function casts(): array
    {
        return [
            'smtp_enabled' => 'boolean',
            'smtp_port' => 'integer',
            'smtp_username' => 'encrypted',
            'smtp_password' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        if (config('mail.okgv.managed_by_env')) {
            return self::fromEnvironment();
        }

        return self::query()->firstOrCreate([], [
            'smtp_enabled' => false,
            'mailer_transport' => 'smtp',
            'smtp_scheme' => 'smtp',
            'smtp_host' => '127.0.0.1',
            'smtp_port' => 587,
            'sendmail_path' => config('mail.mailers.sendmail.path', '/usr/sbin/sendmail -bs -i'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('app.name', 'OKGV'),
        ]);
    }

    public static function fromEnvironment(): self
    {
        $transport = config('mail.default') === 'sendmail' ? 'sendmail' : 'smtp';
        $smtpConfig = config('mail.mailers.smtp', []);

        return new self([
            'smtp_enabled' => true,
            'mailer_transport' => $transport,
            'smtp_scheme' => ($smtpConfig['scheme'] ?? null) ?: 'smtp',
            'smtp_host' => $smtpConfig['host'] ?? null,
            'smtp_port' => $smtpConfig['port'] ?? null,
            'smtp_username' => $smtpConfig['username'] ?? null,
            'smtp_password' => $smtpConfig['password'] ?? null,
            'sendmail_path' => config('mail.mailers.sendmail.path', '/usr/sbin/sendmail -bs -i'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ]);
    }
}
