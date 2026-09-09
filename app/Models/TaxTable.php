<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxTable extends Model
{
    protected $table = 'tax_tables';
    
    protected $fillable = [
        'min_taxable', 'max_taxable', 'fixed_tax', 'excess_percentage', 'effectivity_year'
    ];
}
