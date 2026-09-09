<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_profile_id', 'year', 'sick_leave_total', 'sick_leave_used',
        'vacation_leave_total', 'vacation_leave_used',
        'emergency_leave_total', 'emergency_leave_used',
        'maternity_leave_total', 'maternity_leave_used',
        'paternity_leave_total', 'paternity_leave_used'
    ];
    
    protected $casts = [
        'sick_leave_total' => 'decimal:1',
        'sick_leave_used' => 'decimal:1',
        'vacation_leave_total' => 'decimal:1',
        'vacation_leave_used' => 'decimal:1',
        'emergency_leave_total' => 'decimal:1',
        'emergency_leave_used' => 'decimal:1',
        'maternity_leave_total' => 'decimal:1',
        'maternity_leave_used' => 'decimal:1',
        'paternity_leave_total' => 'decimal:1',
        'paternity_leave_used' => 'decimal:1',
    ];
    
    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }
    
    public function getAvailableSickLeave()
    {
        return $this->sick_leave_total - $this->sick_leave_used;
    }
    
    public function getAvailableVacationLeave()
    {
        return $this->vacation_leave_total - $this->vacation_leave_used;
    }
    
    public function getAvailableEmergencyLeave()
    {
        return $this->emergency_leave_total - $this->emergency_leave_used;
    }

    public function getAvailableMaternityLeave()
    {
        return $this->maternity_leave_total - $this->maternity_leave_used;
    }

    public function getAvailablePaternityLeave()
    {
        return $this->paternity_leave_total - $this->paternity_leave_used;
    }
}
