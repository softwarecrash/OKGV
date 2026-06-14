<?php

namespace App\Models;

use App\Enums\MailRecipientStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'mail_campaign_id',
    'member_id',
    'name',
    'email',
    'status',
    'error_message',
    'sent_at',
])]
class MailCampaignRecipient extends Model
{
    protected function casts(): array
    {
        return [
            'status' => MailRecipientStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MailCampaign::class, 'mail_campaign_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
