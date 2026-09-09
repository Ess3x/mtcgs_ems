<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'name', 'class_code', 'room', 'start_time', 'end_time', 'break_start', 'break_end', 'working_days', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function employeeProfiles()
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function assignedEmployees()
    {
        return $this->belongsToMany(EmployeeProfile::class, 'employee_shift_assignments');
    }

    public function getWorkingDaysListAttribute(): array
    {
        return array_filter(array_map('trim', explode(',', $this->working_days ?? '')));
    }
}
