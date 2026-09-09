<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $table = 'attendance_logs';
    
    protected $fillable = [
        'employee_id', 'employee_profile_id', 'branch_id', 'attendance_date',
        'am_in', 'am_out', 'break_in', 'break_out', 'pm_in', 'pm_out',
        'status', 'late_minutes', 'overtime_hours', 'verification_method', 'override_status',
        'override_reason', 'corrected_time_in', 'corrected_pm_in', 'corrected_time_out', 'override_requested_by', 'override_reviewed_by', 'override_reviewed_at'
    ];
    
    protected $casts = [
        'attendance_date' => 'date',
        'am_in' => 'datetime',
        'am_out' => 'datetime',
        'pm_in' => 'datetime',
        'pm_out' => 'datetime',
        'break_in' => 'datetime',
        'break_out' => 'datetime',
        'override_reviewed_at' => 'datetime',
        'corrected_time_in' => 'datetime',
        'corrected_pm_in' => 'datetime',
        'corrected_time_out' => 'datetime',
    ];
    
    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function getDtrStatus(): string
    {
        if ($this->override_status === 'approved') {
            return 'Present';
        }

        $rawStatus = strtolower(trim((string) $this->status));
        if (in_array($rawStatus, ['leave', 'leave_paid', 'on leave'], true)) {
            return 'Leave';
        }
        if (in_array($rawStatus, ['absent', 'a'], true)) {
            return 'Absent';
        }

        $hasMorning = (bool) $this->am_in;
        $hasAfternoon = (bool) $this->pm_in || (bool) $this->pm_out;
        if (!$hasMorning || !$hasAfternoon) {
            return 'Half Day';
        }

        $shift = $this->employeeProfile?->shift;
        $start = $shift?->start_time ?: '07:00:00';
        $end = $shift?->end_time ?: '17:00:00';
        $scheduledStart = $this->am_in->copy()->setTimeFromTimeString($start);
        $scheduledEnd = ($this->pm_out ?: $this->pm_in)->copy()->setTimeFromTimeString($end);
        $isLate = $this->am_in->gt($scheduledStart) || (int) $this->late_minutes > 0;
        $isEarlyOut = (bool) $this->pm_out && $this->pm_out->lt($scheduledEnd);

        if ($isLate && $isEarlyOut) {
            return 'Late / Early Out';
        }
        if ($isLate) {
            return 'Late';
        }
        if ($isEarlyOut) {
            return 'Early Out';
        }

        return 'Present';
    }
    
    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class);
    }
    
    // Calculate total hours worked
    public function getTotalHoursAttribute()
    {
        $total = 0;
        
        if ($this->am_in && $this->am_out) {
            $total += $this->am_in->diffInHours($this->am_out);
        }
        if ($this->pm_in && $this->pm_out) {
            $total += $this->pm_in->diffInHours($this->pm_out);
        }
        
        return $total;
    }
    
    // Get formatted time for DTR
    public function getFormattedTime($column)
    {
        if ($this->$column) {
            return date('h:i A', strtotime($this->$column));
        }
        return '--:--';
    }
}
