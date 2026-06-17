<?php

namespace AlbrachtSystems\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WachtwoordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $resetUrl,
        public string $naam,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Wachtwoord opnieuw instellen – ' . config('auth-module.app_name'));
    }

    public function content(): Content
    {
        return new Content(view: 'auth-module::emails.wachtwoord-reset');
    }
}
