<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a clinic doctor adds an employee from the doctor app: their sign-in
 * email and password for the Noraya app.
 */
class ClinicEmployeeCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    /** Matches the logo in the template, whatever APP_NAME is set to. */
    public string $brand = 'Noraya';

    public function __construct(
        public string $employeeName,
        public string $clinicName,
        public ?string $clinicAddress,
        public string $addedBy,
        public string $loginEmail,
        public string $password,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been added to ' . $this->clinicName . ' on ' . $this->brand,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.clinic_employee_credentials',
        );
    }
}
