<?php

namespace App\Mail;

use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeStatusChangePendingApproval extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeProfile $employee,
        public string $previousStatus,
        public string $newStatus,
        public User $requestedBy,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Employee Status Change Requires System Administrator Approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-status-change-pending-approval',
            with: [
                'employee' => $this->employee,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
                'requestedBy' => $this->requestedBy,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
