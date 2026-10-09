<?php

namespace App\Mail;

use App\Models\EmployeeProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveCreditsUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeProfile $employeeProfile,
        public array $credits,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Leave Credits Have Been Updated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave-credits-updated',
            with: [
                'employeeProfile' => $this->employeeProfile,
                'credits' => $this->credits,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
