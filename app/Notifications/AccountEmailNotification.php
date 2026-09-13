<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $subject,
        private readonly string $intro,
        private readonly string $actionText,
        private readonly string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->subject)
            ->greeting("Hallo {$notifiable->name},")
            ->line($this->intro)
            ->action($this->actionText, $this->url)
            ->line('Diese Benachrichtigung kannst du in deinem Konto deaktivieren.');

        if (array_key_exists('okgv_smtp', config('mail.mailers', []))) {
            $message->mailer('okgv_smtp');
        }

        return $message;
    }
}
