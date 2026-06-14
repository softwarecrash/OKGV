<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'action',
    'subject_type',
    'subject_id',
    'ip_address',
    'user_agent',
    'metadata',
])]
#[Hidden(['ip_address', 'user_agent'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'metadata' => 'encrypted:array',
        ];
    }
}
