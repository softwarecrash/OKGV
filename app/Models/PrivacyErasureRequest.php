<?php

namespace App\Models;

use App\Enums\PrivacyErasureStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'requested_by',
    'status',
    'requested_at',
    'reviewed_by',
    'reviewed_at',
    'review_note',
    'blockers',
    'completed_at',
])]
class PrivacyErasureRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PrivacyErasureStatus::class,
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
            'blockers' => 'array',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
