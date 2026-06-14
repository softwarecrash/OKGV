<?php

namespace App\Jobs;

use App\Enums\MailCampaignStatus;
use App\Enums\MailRecipientStatus;
use App\Mail\CampaignMessage;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CommunicationMailConfigurator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendCampaignRecipient implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public readonly int $recipientId,
        public readonly int $actorId,
    ) {}

    public function handle(CommunicationMailConfigurator $configurator): void
    {
        $recipient = MailCampaignRecipient::query()
            ->with('campaign')
            ->findOrFail($this->recipientId);

        if ($recipient->status !== MailRecipientStatus::Pending) {
            return;
        }

        try {
            $configurator->apply();
            Mail::mailer('okgv_smtp')
                ->to($recipient->email, $recipient->name)
                ->send(new CampaignMessage($recipient->campaign, $recipient));

            $recipient->update([
                'status' => MailRecipientStatus::Sent,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $recipient->update([
                'status' => MailRecipientStatus::Failed,
                'error_message' => Str::limit(
                    preg_replace('/\s+/', ' ', $exception->getMessage()),
                    500,
                ),
            ]);
        }

        $this->finalizeCampaign($recipient->mail_campaign_id);
    }

    private function finalizeCampaign(int $campaignId): void
    {
        DB::transaction(function () use ($campaignId): void {
            $campaign = MailCampaign::query()->lockForUpdate()->findOrFail($campaignId);
            $pendingCount = $campaign->recipients()
                ->where('status', MailRecipientStatus::Pending)
                ->count();

            if ($pendingCount > 0) {
                return;
            }

            $sentCount = $campaign->recipients()
                ->where('status', MailRecipientStatus::Sent)
                ->count();
            $failedCount = $campaign->recipients()
                ->where('status', MailRecipientStatus::Failed)
                ->count();

            $campaign->update([
                'status' => $failedCount === 0
                    ? MailCampaignStatus::Sent
                    : MailCampaignStatus::Failed,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'sent_at' => now(),
            ]);

            AuditLogger::log(
                'mail_campaign.sent',
                User::query()->find($this->actorId),
                $campaign,
                [
                    'recipient_group' => $campaign->recipient_group->value,
                    'recipient_count' => $campaign->recipient_count,
                    'sent_count' => $sentCount,
                    'failed_count' => $failedCount,
                ],
            );
        });
    }
}
