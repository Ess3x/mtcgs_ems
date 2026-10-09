<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceProfile extends Model
{
    protected $table = 'finance_profiles';
    
    protected $fillable = [
        'user_id', 'branch_id', 'employee_profile_id', 'employee_number', 'first_name', 'last_name', 'middle_name',
        'position', 'department', 'salary_grade', 'status', 'accessible_branches',
        'can_process_payroll', 'can_approve_payroll', 'can_create_employees', 'can_manage_accounts',
        'authority_granted_by', 'authority_granted_at', 'date_hired',
        'contact_number', 'address', 'profile_photo', 'fingerprint_template', 'signature_path', 'is_fingerprint_registered',
        'basic_salary', 'hourly_rate', 'pending_changes', 'changes_requested_by', 'changes_requested_at'
    ];
    
    protected $casts = [
        'accessible_branches' => 'array',
        'is_fingerprint_registered' => 'boolean',
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'pending_changes' => 'array',
        'changes_requested_at' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
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
    
    // Finance officer DTR (attendance)
    public function attendanceLogs()
    {
        // Finance officers will use employee_profile_id, so we need to link
        // For now, finance officers need an employee profile too
        return $this->hasMany(AttendanceLog::class, 'employee_profile_id', 'employee_profile_id');
    }
    
    // Link to employee profile for DTR
    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }
}
