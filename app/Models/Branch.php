<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['branch_code', 'branch_name', 'address'];

    public function employees()
    {
        return $this->hasMany(EmployeeProfile::class, 'branch_id');
    }

    public function employeeCount()
    {
        return $this->employees()->count();
    }
}
