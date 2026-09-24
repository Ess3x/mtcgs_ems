<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashCharge extends Model
{
    protected $fillable = [
        'employee_profile_id',
        'branch_id',
        'amount',
        'installment_per_cutoff',
        'reason',
        'requested_by',
        'approved_by',
        'status',
        'archived_from_status',
        'archived_by_role',
        'approved_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'installment_per_cutoff' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
