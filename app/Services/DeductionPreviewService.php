<?php

namespace App\Services;

use App\Models\SssContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;
use App\Models\TaxTable;

class DeductionPreviewService
{
    /**
     * Get a preview of all mandatory deductions for an employee
     * This shows what deductions would be applied based on their salary
     */
    public function getMandatoryDeductionsPreview($monthlySalary)
    {
        return [
            'sss' => [
                'name' => 'SSS (Social Security System)',
                'amount' => $this->calculateSSS($monthlySalary),
                'description' => 'Government social security contribution',
                'is_mandatory' => true,
            ],
            'philhealth' => [
                'name' => 'PhilHealth',
                'amount' => $this->calculatePhilHealth($monthlySalary),
                'description' => 'Health insurance contribution',
                'is_mandatory' => true,
            ],
            'pagibig' => [
                'name' => 'Pag-IBIG Fund',
                'amount' => $this->calculatePagIBIG($monthlySalary),
                'description' => 'Home development mutual fund',
                'is_mandatory' => true,
                'additional_available' => true,
                'additional_description' => 'You can add more to your Pag-IBIG savings',
            ],
            'withholding_tax' => [
                'name' => 'Withholding Tax (BIR)',
                'amount' => 0, // This is calculated after other deductions
                'description' => 'Bureau of Internal Revenue tax',
                'is_mandatory' => true,
                'note' => 'Will be calculated based on final taxable income',
            ],
        ];
    }

    /**
     * Get examples of optional additional deductions employee can add
     */
    public function getOptionalDeductionExamples()
    {
        return [
            [
                'type' => 'additional_pagibig',
                'name' => 'Additional Pag-IBIG Contribution',
                'description' => 'Increase your Pag-IBIG savings for home development',
                'min_amount' => 100,
                'max_amount' => 10000,
                'default_amount' => 500,
                'icon' => 'fas fa-home',
                'color' => 'warning',
            ],
            [
                'type' => 'savings_deduction',
                'name' => 'Employee Savings Program',
                'description' => 'Automatic deduction to your savings account',
                'min_amount' => 100,
                'max_amount' => 50000,
                'default_amount' => 1000,
                'icon' => 'fas fa-piggy-bank',
                'color' => 'info',
            ],
            [
                'type' => 'union_dues',
                'name' => 'Union Dues',
                'description' => 'Union membership fees and contributions',
                'min_amount' => 100,
                'max_amount' => 5000,
                'default_amount' => 500,
                'icon' => 'fas fa-users',
                'color' => 'primary',
            ],
            [
                'type' => 'loan_deduction',
                'name' => 'Company Loan Payment',
                'description' => 'Installment payment for approved company loans',
                'min_amount' => 500,
                'max_amount' => 100000,
                'default_amount' => 5000,
                'icon' => 'fas fa-handshake',
                'color' => 'danger',
            ],
        ];
    }

    /**
     * Calculate total with optional deductions
     */
    public function calculateNetPayWithOptionalDeductions($grossPay, $mandatoryDeductions, $optionalDeductions = [])
    {
        $totalDeductions = array_sum(array_column($mandatoryDeductions, 'amount'));
        
        foreach ($optionalDeductions as $deduction) {
            $totalDeductions += $deduction['amount'];
        }

        return $grossPay - $totalDeductions;
    }

    /**
     * Calculate SSS contribution based on salary bracket
     */
    private function calculateSSS($monthlySalary)
    {
        $sss = SssContribution::where('min_salary', '<=', $monthlySalary)
            ->where('max_salary', '>=', $monthlySalary)
            ->first();

        if (!$sss) {
            $sss = SssContribution::orderBy('max_salary', 'desc')->first();
        }

        return $sss ? round($sss->employee_share, 2) : 0;
    }

    /**
     * Calculate PhilHealth contribution (3% of salary, min ₱300, max ₱1,800)
     */
    private function calculatePhilHealth($monthlySalary)
    {
        $premium = $monthlySalary * 0.03;
        $totalShare = min(max($premium, 300), 1800);
        $employeeShare = $totalShare / 2;

        return round($employeeShare, 2);
    }

    /**
     * Calculate Pag-IBIG contribution
     */
    private function calculatePagIBIG($monthlySalary)
    {
        if ($monthlySalary <= 1500) {
            return 0;
        }

        return 100; // Fixed ₱100 employee share
    }

    /**
     * Calculate BIR Withholding Tax based on taxable income
     */
    public function calculateWithholdingTax($taxableIncome)
    {
        if ($taxableIncome <= 0) {
            return 0;
        }

        $taxBracket = TaxTable::where('min_taxable', '<=', $taxableIncome)
            ->where('max_taxable', '>=', $taxableIncome)
            ->first();

        if (!$taxBracket) {
            $taxBracket = TaxTable::orderBy('max_taxable', 'desc')->first();
        }

        if (!$taxBracket) {
            return 0;
        }

        $excess = $taxableIncome - $taxBracket->min_taxable;
        $taxDue = $taxBracket->fixed_tax + ($excess * ($taxBracket->excess_percentage / 100));

        return round($taxDue, 2);
    }

    /**
     * Format deduction for display
     */
    public function formatDeductionDisplay($deduction)
    {
        return [
            'name' => $deduction['name'] ?? '',
            'amount' => '₱ ' . number_format($deduction['amount'], 2),
            'description' => $deduction['description'] ?? '',
            'is_mandatory' => $deduction['is_mandatory'] ?? false,
        ];
    }
}
