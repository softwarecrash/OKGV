<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'recipient_name',
    'street',
    'zip',
    'city',
    'subject',
    'body',
    'association_snapshot',
    'created_by',
])]
class Letter extends Model
{
    protected function casts(): array
    {
        return [
            'association_snapshot' => 'array',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
