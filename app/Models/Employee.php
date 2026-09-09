<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id', 'branch_id', 'employee_number', 'first_name', 'last_name',
        'middle_name', 'position', 'department', 'date_hired', 'basic_salary',
        'contact_number', 'address', 'fingerprint_template', 'is_fingerprint_registered'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
