<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\AttendanceLog;
use App\Models\EmployeeProfile;

class AttendanceRecordedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $attendanceLog;
    public $employeeProfile;
    public $attendanceType;

    /**
     * Create a new event instance.
     */
    public function __construct(AttendanceLog $attendanceLog, EmployeeProfile $employeeProfile, string $attendanceType)
    {
        $this->attendanceLog = $attendanceLog;
        $this->employeeProfile = $employeeProfile;
        $this->attendanceType = $attendanceType;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('employee.' . $this->employeeProfile->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'attendance.recorded';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'attendance_type' => $this->attendanceType,
            'timestamp' => now()->toISOString(),
            'attendance_data' => [
                'date' => $this->attendanceLog->attendance_date->format('Y-m-d'),
                'am_in' => $this->attendanceLog->am_in?->format('h:i A'),
                'am_out' => $this->attendanceLog->am_out?->format('h:i A'),
                'pm_in' => $this->attendanceLog->pm_in?->format('h:i A'),
                'pm_out' => $this->attendanceLog->pm_out?->format('h:i A'),
                'late_minutes' => $this->attendanceLog->late_minutes,
                'overtime_hours' => $this->attendanceLog->overtime_hours,
                'status' => $this->attendanceLog->status,
            ]
        ];
    }
}
