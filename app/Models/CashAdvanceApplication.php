<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashAdvanceApplication extends Model
{
    protected $fillable = [
        'employee_profile_id', 'requested_amount', 'approved_amount', 'installment_amount',
        'installments', 'purpose', 'eligibility_category', 'status', 'rejection_reason',
        'deducted_installments', 'deduction_started_at', 'completed_at',
        'fo_reviewed_by', 'fo_reviewed_at', 'bh_reviewed_by', 'bh_reviewed_at',
        'hr_reviewed_by', 'hr_reviewed_at', 'fh_reviewed_by', 'fh_reviewed_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'fo_reviewed_at' => 'datetime',
        'bh_reviewed_at' => 'datetime',
        'hr_reviewed_at' => 'datetime',
        'fh_reviewed_at' => 'datetime',
        'deduction_started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function foReviewer()
    {
        return $this->belongsTo(User::class, 'fo_reviewed_by');
    }

    public function bhReviewer()
    {
        return $this->belongsTo(User::class, 'bh_reviewed_by');
    }

    public function hrReviewer()
    {
        return $this->belongsTo(User::class, 'hr_reviewed_by');
    }

    public function fhReviewer()
    {
        return $this->belongsTo(User::class, 'fh_reviewed_by');
    }
}
