<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SmtpTestMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'SMTP-Test '.config('app.name', 'OKGV'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.smtp-test');
    }
}
