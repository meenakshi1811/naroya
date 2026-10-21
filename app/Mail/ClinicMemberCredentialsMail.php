<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClinicMemberCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $roleLabel,
        public string $clinicName,
        public string $loginEmail,
        public string $password,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name').' account for '.$this->clinicName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.clinic_member_credentials',
        );
    }
}
