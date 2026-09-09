<?php

namespace App\Mail;

use App\Models\EmployeeProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeProfile $employeeProfile,
        public string $previousStatus,
        public string $newStatus,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Employment Status Has Been Updated');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-status-changed',
            with: [
                'employeeProfile' => $this->employeeProfile,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}