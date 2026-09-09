<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAdditionalDeduction extends Model
{
    protected $table = 'employee_additional_deductions';

    protected $fillable = [
        'payroll_entry_id',
        'employee_profile_id',
        'deduction_type',
        'description',
        'amount',
        'is_active'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to PayrollEntry
     */
    public function payrollEntry()
    {
        return $this->belongsTo(PayrollEntry::class);
    }

    /**
     * Relationship to EmployeeProfile
     */
    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
