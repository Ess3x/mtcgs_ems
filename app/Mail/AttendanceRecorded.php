<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\AttendanceLog;
use App\Models\EmployeeProfile;

class AttendanceRecorded extends Mailable
{
    use Queueable, SerializesModels;

    public $attendanceLog;
    public $employeeProfile;
    public $attendanceType;

    /**
     * Create a new message instance.
     */
    public function __construct(AttendanceLog $attendanceLog, EmployeeProfile $employeeProfile, string $attendanceType)
    {
        $this->attendanceLog = $attendanceLog;
        $this->employeeProfile = $employeeProfile;
        $this->attendanceType = $attendanceType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Attendance Recorded - ' . ucwords(str_replace('_', ' ', $this->attendanceType)),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance-recorded',
            with: [
                'attendanceLog' => $this->attendanceLog,
                'employeeProfile' => $this->employeeProfile,
                'attendanceType' => $this->attendanceType,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
