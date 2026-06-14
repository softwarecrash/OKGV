<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $applicationName = config('app.name', 'OKGV');

        return (new MailMessage)
            ->subject("E-Mail-Adresse für {$applicationName} bestätigen")
            ->greeting("Hallo {$notifiable->name},")
            ->line("dein Benutzerkonto für {$applicationName} wurde freigegeben.")
            ->line('Bitte bestätige jetzt deine E-Mail-Adresse, bevor du die Anwendung verwendest.')
            ->action('E-Mail-Adresse bestätigen', $this->verificationUrl($notifiable))
            ->line('Dieser Bestätigungslink ist 60 Minuten gültig.')
            ->line('Falls du dieses Konto nicht angefordert hast, informiere bitte den Vereinsvorstand.');
    }
}
