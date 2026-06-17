<?php

namespace AlbrachtSystems\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailWijzigenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $bevestigUrl,
        public string $naam,
        public string $nieuwEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bevestig je nieuwe e-mailadres – ' . config('auth-module.app_name'));
    }

    public function content(): Content
    {
        return new Content(view: 'auth-module::emails.email-wijzigen');
    }
}
