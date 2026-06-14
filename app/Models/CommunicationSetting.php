<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'smtp_enabled',
    'smtp_scheme',
    'smtp_host',
    'smtp_port',
    'smtp_username',
    'smtp_password',
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
            'smtp_scheme' => 'smtp',
            'smtp_host' => '127.0.0.1',
            'smtp_port' => 587,
            'from_address' => config('mail.from.address'),
            'from_name' => config('app.name', 'OKGV'),
        ]);
    }
}
