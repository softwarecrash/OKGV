<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'share_name',
    'share_email',
    'share_phone',
    'share_mobile',
    'share_address',
    'consented_at',
])]
class MemberPrivacySetting extends Model
{
    protected function casts(): array
    {
        return [
            'share_name' => 'boolean',
            'share_email' => 'boolean',
            'share_phone' => 'boolean',
            'share_mobile' => 'boolean',
            'share_address' => 'boolean',
            'consented_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sharesAnything(): bool
    {
        return $this->share_name
            || $this->share_email
            || $this->share_phone
            || $this->share_mobile
            || $this->share_address;
    }
}
