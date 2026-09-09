<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\LeaveRequest;
use App\Models\EmployeeProfile;

class LeaveRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $leaveRequest;
    public $employeeProfile;

    /**
     * Create a new message instance.
     */
    public function __construct(LeaveRequest $leaveRequest, EmployeeProfile $employeeProfile)
    {
        $this->leaveRequest = $leaveRequest;
        $this->employeeProfile = $employeeProfile;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Leave Request Has Been Declined',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.leave-rejected',
            with: [
                'leaveRequest' => $this->leaveRequest,
                'employeeProfile' => $this->employeeProfile,
            ],
        );
    }
}
