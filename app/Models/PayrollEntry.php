<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollEntry extends Model
{
    protected $table = 'payroll_entries';
    
    protected $fillable = [
        'payroll_period_id', 'employee_profile_id', 'branch_id', 'dtr_id', 'basic_pay',
        'overtime_pay', 'overtime_hours', 'days_present', 'days_absent',
        'late_deduction', 'absent_deduction', 'leave_deduction', 'allowances', 'bonuses', 'gross_pay',
        'sss_contribution', 'philhealth_contribution', 'pagibig_contribution',
        'withholding_tax', 'cash_advance_deduction', 'total_deductions', 'net_pay', 'status', 'payroll_breakdown',
        'payslip_sent_at', 'payslip_sent_to'
        , 'correction_stage', 'correction_reason', 'correction_returned_by', 'correction_returned_at'
    ];
    
    protected $casts = [
        'basic_pay' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'cash_advance_deduction' => 'decimal:2',
        'payslip_sent_at' => 'datetime',
        'correction_returned_at' => 'datetime',
    ];

    public function getBasicPayAttribute($value)
    {
        $base = (float) ($value ?? 0);

        if ($base <= 0 && $this->employeeProfile) {
            return (float) ($this->employeeProfile->basic_salary ?? 0);
        }

        return $base;
    }
    
    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
    
    public function dtr()
    {
        return $this->belongsTo(DTR::class, 'dtr_id');
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship to additional deductions
     */
    public function additionalDeductions()
    {
        return $this->hasMany(EmployeeAdditionalDeduction::class)->where('is_active', true);
    }

    /**
     * Get total additional deductions
     */
    public function getTotalAdditionalDeductions()
    {
        return $this->additionalDeductions->sum('amount') ?? 0;
    }

    /**
     * Calculate net pay including additional deductions
     */
    public function getAdjustedNetPay()
    {
        $additionalDeductions = $this->getTotalAdditionalDeductions();
        return $this->net_pay - $additionalDeductions;
    }
    
    // Alias for backward compatibility
    public function employee()
    {
        return $this->employeeProfile();
    }
}
