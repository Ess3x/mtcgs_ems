<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagibigContribution extends Model
{
    protected $table = 'pagibig_contributions';
    
    protected $fillable = [
        'min_salary', 'max_salary', 'employee_share', 'employer_share', 'effectivity_year'
    ];
}
