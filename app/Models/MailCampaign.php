<?php

namespace App\Models;

use App\Enums\MailCampaignStatus;
use App\Enums\MailRecipientGroup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'subject',
    'body',
    'association_snapshot',
    'recipient_group',
    'status',
    'recipient_count',
    'sent_count',
    'failed_count',
    'created_by',
    'sent_at',
])]
class MailCampaign extends Model
{
    protected function casts(): array
    {
        return [
            'recipient_group' => MailRecipientGroup::class,
            'association_snapshot' => 'array',
            'status' => MailCampaignStatus::class,
            'recipient_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MailCampaignRecipient::class);
    }
}
