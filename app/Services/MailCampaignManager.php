<?php

namespace App\Services;

use App\Enums\MailCampaignStatus;
use App\Jobs\SendCampaignRecipient;
use App\Models\MailCampaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MailCampaignManager
{
    public function __construct(
        private readonly MailRecipientResolver $resolver,
        private readonly CommunicationMailConfigurator $configurator,
        private readonly AssociationDocumentProfile $associationProfile,
    ) {}

    public function send(MailCampaign $campaign, User $actor): MailCampaign
    {
        $this->configurator->apply();
        $resolvedRecipients = $this->resolver->resolve($campaign->recipient_group);

        if ($resolvedRecipients->isEmpty()) {
            throw ValidationException::withMessages([
                'recipient_group' => 'Für diese Empfängergruppe wurde keine gültige E-Mail-Adresse gefunden.',
            ]);
        }

        $campaign = DB::transaction(function () use ($campaign, $resolvedRecipients): MailCampaign {
            $campaign = MailCampaign::query()->lockForUpdate()->findOrFail($campaign->id);

            if ($campaign->status !== MailCampaignStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Diese Serienmail wurde bereits versendet oder befindet sich im Versand.',
                ]);
            }

            foreach ($resolvedRecipients as $recipient) {
                $campaign->recipients()->create($recipient);
            }

            $campaign->update([
                'status' => MailCampaignStatus::Sending,
                'recipient_count' => $resolvedRecipients->count(),
                'association_snapshot' => $this->associationProfile->snapshot(),
            ]);

            return $campaign;
        });

        $campaign->recipients()->eachById(
            fn ($recipient) => SendCampaignRecipient::dispatch($recipient->id, $actor->id),
        );

        return $campaign->refresh();
    }
}
