<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $applicationName = config('app.name', 'OKGV');

        $message = (new MailMessage)
            ->subject("E-Mail-Adresse für {$applicationName} bestätigen")
            ->greeting("Hallo {$notifiable->name},")
            ->line("dein Benutzerkonto für {$applicationName} wurde angelegt und wartet auf Freigabe.")
            ->line('Bitte bestätige deine E-Mail-Adresse. Der Vorstand oder ein Administrator prüft die Zugangsanfrage separat.')
            ->action('E-Mail-Adresse bestätigen', $this->verificationUrl($notifiable))
            ->line('Dieser Bestätigungslink ist 60 Minuten gültig.')
            ->line('Falls du dieses Konto nicht angefordert hast, informiere bitte den Vereinsvorstand.');

        if (array_key_exists('okgv_smtp', config('mail.mailers', []))) {
            $message->mailer('okgv_smtp');
        }

        return $message;
    }
}
