<?php

namespace App\Mail;

use App\Models\FinanceProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FinanceOfficerChangePendingApproval extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FinanceProfile $financeProfile,
        public User $requestedBy,
        public array $changes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Finance Officer Change Requires System Administrator Approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.finance-officer-change-pending-approval',
            with: [
                'financeProfile' => $this->financeProfile,
                'requestedBy' => $this->requestedBy,
                'changes' => $this->changes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
