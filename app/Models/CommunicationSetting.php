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
}
