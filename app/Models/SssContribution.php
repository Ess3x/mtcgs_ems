<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SssContribution extends Model
{
    protected $table = 'sss_contributions';
    
    protected $fillable = [
        'min_salary', 'max_salary', 'employee_share', 'employer_share', 'effectivity_year'
    ];
}
