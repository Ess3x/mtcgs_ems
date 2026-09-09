<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilhealthContribution extends Model
{
    protected $table = 'philhealth_contributions';
    
    protected $fillable = [
        'min_salary', 'max_salary', 'employee_share', 'employer_share', 'effectivity_year'
    ];
}
