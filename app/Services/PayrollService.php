<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use App\Models\SssContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;
use App\Models\TaxTable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollService
{
    // Compute basic pay based on attendance
    public function computeBasicPay($employee, $period)
    {
        $dailyRate = $employee->basic_salary / 22; // 22 working days per month
        
        $attendanceLogs = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$period->start_date, $period->end_date])
            ->get();
        
        $daysPresent = $attendanceLogs->whereNotNull('time_in')->count();
        $daysLate = $attendanceLogs->where('late_minutes', '>', 0)->count();
        $workingDays = app(\App\Services\WorkingDayService::class)->countWorkingDays(
            $period->start_date,
            $period->end_date,
            $employee->branch_id
        );
        
        $basicPay = $dailyRate * $daysPresent;
        $lateDeduction = ($daysLate * $dailyRate * 0.25); // 15 minutes late deduction
        
        return [
            'basic_pay' => round($basicPay, 2),
            'daily_rate' => round($dailyRate, 2),
            'days_present' => $daysPresent,
            'days_absent' => max(0, $workingDays - $daysPresent),
            'late_deduction' => round($lateDeduction, 2),
        ];
    }
    
    // Compute SSS contribution
    public function computeSSS($monthlySalary)
    {
        $sss = SssContribution::where('min_salary', '<=', $monthlySalary)
            ->where('max_salary', '>=', $monthlySalary)
            ->first();
        
        if (!$sss) {
            // Get the highest bracket
            $sss = SssContribution::orderBy('max_salary', 'desc')->first();
        }
        
        return [
            'employee_share' => $sss->employee_share ?? 0,
            'employer_share' => $sss->employer_share ?? 0,
            'total' => ($sss->employee_share ?? 0) + ($sss->employer_share ?? 0),
        ];
    }
    
    // Compute PhilHealth contribution
    public function computePhilHealth($monthlySalary)
    {
        $premium = $monthlySalary * 0.03; // 3% total premium
        
        // Minimum ₱300, Maximum ₱1,800
        $totalShare = min(max($premium, 300), 1800);
        $employeeShare = $totalShare / 2;
        $employerShare = $totalShare / 2;
        
        return [
            'employee_share' => round($employeeShare, 2),
            'employer_share' => round($employerShare, 2),
            'total' => round($totalShare, 2),
        ];
    }
    
    // Compute Pag-IBIG contribution
    public function computePagIBIG($monthlySalary)
    {
        if ($monthlySalary <= 1500) {
            $employeeShare = 0;
            $employerShare = 0;
            $total = 0;
        } else {
            $employeeShare = 100;
            $employerShare = 100;
            $total = 200;
        }
        
        return [
            'employee_share' => $employeeShare,
            'employer_share' => $employerShare,
            'total' => $total,
        ];
    }
    
    // Compute BIR Withholding Tax
    public function computeTax($taxableIncome)
    {
        $taxBracket = TaxTable::where('min_taxable', '<=', $taxableIncome)
            ->where('max_taxable', '>=', $taxableIncome)
            ->first();
        
        if (!$taxBracket) {
            $taxBracket = TaxTable::orderBy('max_taxable', 'desc')->first();
        }
        
        $excess = $taxableIncome - $taxBracket->min_taxable;
        $taxDue = $taxBracket->fixed_tax + ($excess * ($taxBracket->excess_percentage / 100));
        
        return round($taxDue, 2);
    }
    
    // Compute overtime pay
    public function computeOvertime($employee, $period)
    {
        return [
            'hours' => 0,
            'pay' => 0,
        ];
    }
    
    // Complete payroll computation for an employee
    public function computeEmployeePayroll($employeeId, $periodId)
    {
        $employee = Employee::findOrFail($employeeId);
        $period = PayrollPeriod::findOrFail($periodId);
        
        $basicPayData = $this->computeBasicPay($employee, $period);
        $overtimeData = $this->computeOvertime($employee, $period);
        
        $grossPay = $basicPayData['basic_pay'] + $overtimeData['pay'];
        
        $sss = $this->computeSSS($employee->basic_salary);
        $philhealth = $this->computePhilHealth($employee->basic_salary);
        $pagibig = $this->computePagIBIG($employee->basic_salary);
        
        $taxableIncome = $grossPay - $sss['employee_share'] - $philhealth['employee_share'] - $pagibig['employee_share'];
        $tax = $this->computeTax($taxableIncome);
        
        $totalDeductions = $sss['employee_share'] + $philhealth['employee_share'] + $pagibig['employee_share'] + $tax + $basicPayData['late_deduction'];
        $netPay = $grossPay - $totalDeductions;
        
        return [
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'payroll_period_id' => $periodId,
            'basic_pay' => $basicPayData['basic_pay'],
            'overtime_pay' => $overtimeData['pay'],
            'overtime_hours' => $overtimeData['hours'],
            'days_present' => $basicPayData['days_present'],
            'days_absent' => $basicPayData['days_absent'],
            'late_deduction' => $basicPayData['late_deduction'],
            'gross_pay' => $grossPay,
            'sss_contribution' => $sss['employee_share'],
            'philhealth_contribution' => $philhealth['employee_share'],
            'pagibig_contribution' => $pagibig['employee_share'],
            'withholding_tax' => $tax,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'status' => 'calculated',
        ];
    }
    
    // Process payroll for entire period
    public function processPayroll($periodId)
    {
        $period = PayrollPeriod::findOrFail($periodId);
        $employees = Employee::all();
        
        DB::beginTransaction();
        
        try {
            foreach ($employees as $employee) {
                $computed = $this->computeEmployeePayroll($employee->id, $periodId);
                
                PayrollEntry::updateOrCreate(
                    ['payroll_period_id' => $periodId, 'employee_id' => $employee->id],
                    $computed
                );
            }
            
            // Compute totals
            $totals = PayrollEntry::where('payroll_period_id', $periodId)
                ->select(
                    DB::raw('SUM(gross_pay) as total_gross'),
                    DB::raw('SUM(total_deductions) as total_deductions'),
                    DB::raw('SUM(net_pay) as total_net')
                )
                ->first();
            
            $period->update([
                'status' => 'completed',
                'processed_at' => now(),
                'total_gross' => $totals->total_gross ?? 0,
                'total_deductions' => $totals->total_deductions ?? 0,
                'total_net' => $totals->total_net ?? 0,
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Payroll processed successfully',
                'totals' => $totals,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to process payroll: ' . $e->getMessage(),
            ];
        }
    }
}
