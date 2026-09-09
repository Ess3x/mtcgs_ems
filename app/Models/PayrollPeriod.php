<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    protected $table = 'payroll_periods';
    
    protected $fillable = [
        'name', 'branch_id', 'period_code', 'period_type', 'start_date', 'end_date',
        'cutoff_date', 'payment_date', 'status', 'processed_by',
        'processed_at', 'approved_by', 'approved_at',
        'admin_approval_stage',
        'hr_approved_by', 'hr_approved_at',
        'branch_submitted_at', 'branch_approved_by', 'branch_approved_at',
        'finance_submitted_by', 'finance_submitted_at', 'correction_stage', 'correction_reason',
        'correction_returned_by', 'correction_returned_at',
        'total_gross', 'total_deductions', 'total_net'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cutoff_date' => 'date',
        'payment_date' => 'date',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'branch_submitted_at' => 'datetime',
        'branch_approved_at' => 'datetime',
        'finance_submitted_at' => 'datetime',
        'correction_returned_at' => 'datetime',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
    ];
    
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function entries()
    {
        return $this->hasMany(PayrollEntry::class);
    }
    
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
    
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function hrApprovedBy()
    {
        return $this->belongsTo(User::class, 'hr_approved_by');
    }

    public function branchApprovedBy()
    {
        return $this->belongsTo(User::class, 'branch_approved_by');
    }

    public function financeSubmittedBy()
    {
        return $this->belongsTo(User::class, 'finance_submitted_by');
    }
}
