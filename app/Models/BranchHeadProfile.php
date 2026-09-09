<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchHeadProfile extends Model
{
    protected $table = 'branch_heads';
    
    protected $fillable = [
        'user_id', 'branch_id', 'employee_profile_id', 'employee_number', 'first_name', 'last_name', 'middle_name',
        'position', 'department', 'contact_number', 'address', 'date_hired',
        'fingerprint_template', 'is_fingerprint_registered'
    ];
    
    protected $casts = [
        'is_fingerprint_registered' => 'boolean',
        'date_hired' => 'date',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }
    
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'employee_profile_id', 'employee_profile_id');
    }
}
