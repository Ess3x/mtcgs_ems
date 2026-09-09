<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    protected $table = 'employee_profiles';
    
    protected $fillable = [
        'user_id', 'branch_id', 'shift_id', 'employee_number', 'first_name', 'last_name', 'middle_name',
        'suffix', 'date_of_birth', 'gender', 'civil_status', 'position', 'department',
        'employment_type', 'date_hired', 'status', 'pending_status', 'status_change_requested_by',
        'status_change_requested_at', 'status_change_approved_by', 'status_change_approved_at',
        'status_change_rejection_reason', 'date_resigned', 'basic_salary', 'hourly_rate',
        'contact_number', 'emergency_contact_name', 'emergency_contact_number',
        'address', 'profile_photo', 'fingerprint_template', 'is_fingerprint_registered', 'signature_path',
        'sss_number', 'philhealth_number', 'pagibig_number', 'tin_number'
    ];
    
    protected $casts = [
        'date_of_birth' => 'date',
        'date_hired' => 'date',
        'date_resigned' => 'date',
        'status_change_requested_at' => 'datetime',
        'status_change_approved_at' => 'datetime',
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'is_fingerprint_registered' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'employee_shift_assignments');
    }
    
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function hasRegisteredFingerprint(): bool
    {
        $template = trim((string) ($this->fingerprint_template ?? ''));

        return (bool) $this->is_fingerprint_registered || ($template !== '' && strtolower($template) !== 'null');
    }
    
    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'employee_profile_id');
    }
    
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'employee_profile_id');
    }
    
    public function payrollEntries()
    {
        return $this->hasMany(PayrollEntry::class, 'employee_profile_id');
    }
    
    public function dtrs()
    {
        return $this->hasMany(DTR::class, 'employee_profile_id');
    }

    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class);
    }
}
