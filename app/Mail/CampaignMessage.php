<?php

namespace App\Mail;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Services\AssociationDocumentProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly MailCampaign $campaign,
        public readonly MailCampaignRecipient $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.campaign',
            with: [
                'association' => app(AssociationDocumentProfile::class)
                    ->resolve($this->campaign->association_snapshot),
            ],
        );
    }
}
