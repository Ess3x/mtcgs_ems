<?php

namespace App\Services;

use App\Models\DTR;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\SssContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;
use App\Models\CashAdvanceApplication;
use App\Models\CashCharge;
use App\Models\TaxTable;
use Carbon\Carbon;

class PayrollComputationService
{
    public const SUSPENSION_HOURLY_RATE = 60.0;

    /**
     * Generate payroll entry from approved DTR
     */
    public function generatePayrollFromDTR(DTR $dtr, PayrollPeriod $payrollPeriod)
    {
        $employeeProfile = $dtr->employeeProfile;
        $calculation = $this->buildPayrollCalculation($dtr, $employeeProfile);
        $existing = PayrollEntry::where('dtr_id', $dtr->id)->first();
        $cashAdvances = CashAdvanceApplication::eligibleForPayrollCutoff(
            $employeeProfile->id,
            $payrollPeriod->start_date,
            $payrollPeriod->end_date
        )->orderBy('fh_reviewed_at')->orderBy('created_at')->get();
        $cashAdvanceDeduction = $cashAdvances->sum(fn ($cashAdvance) =>
            (float) ($cashAdvance->approved_amount ?: $cashAdvance->requested_amount)
        ) ?: (float) ($existing->cash_advance_deduction ?? 0);
        $cashCharge = $this->cashChargeDeductionForPayroll($employeeProfile->id, $payrollPeriod);
        $cashChargeDeduction = $cashCharge['amount'];
        $totalDeductions = (float) $calculation['total_deductions'] + $cashAdvanceDeduction + $cashChargeDeduction;

        $payload = [
            'branch_id' => $employeeProfile?->branch_id,
            'basic_pay' => $calculation['basic_pay'],
            'overtime_pay' => $calculation['overtime_pay'],
            'overtime_hours' => $calculation['overtime_hours'],
            'days_present' => $calculation['days_worked'],
            'days_absent' => $calculation['days_absent'],
            'late_deduction' => $calculation['late_deduction'],
            'absent_deduction' => $calculation['absent_deduction'],
            'leave_deduction' => $calculation['leave_deduction'],
            'gross_pay' => $calculation['gross_pay'],
            'sss_contribution' => $calculation['sss_deduction'],
            'philhealth_contribution' => $calculation['philhealth_deduction'],
            'pagibig_contribution' => $calculation['pagibig_deduction'],
            'withholding_tax' => $calculation['withholding_tax'],
            'cash_advance_deduction' => $cashAdvanceDeduction,
            'cash_charge_id' => $cashCharge['id'],
            'cash_charge_deduction' => $cashChargeDeduction,
            'total_deductions' => $totalDeductions,
            'net_pay' => (float) $calculation['gross_pay'] - $totalDeductions,
            'status' => 'draft',
            'payroll_breakdown' => json_encode(array_merge($calculation['breakdown'], [
                'cash_advance_deduction' => $cashAdvanceDeduction,
                'cash_charge_id' => $cashCharge['id'],
                'cash_charge_deduction' => $cashChargeDeduction,
            ])),
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();
            $this->recordCashAdvanceInstallments($cashAdvances);
            return $existing;
        }

        $payrollEntry = PayrollEntry::updateOrCreate(
            [
                'dtr_id' => $dtr->id,
                'payroll_period_id' => $payrollPeriod->id,
                'employee_profile_id' => $employeeProfile->id,
            ],
            $payload
        );

        $this->recordCashAdvanceInstallments($cashAdvances);

        return $payrollEntry;
    }

    private function recordCashAdvanceInstallments($cashAdvances): void
    {
        foreach ($cashAdvances as $cashAdvance) {
            $cashAdvance->update([
                'deducted_installments' => (int) $cashAdvance->installments,
                'status' => 'completed',
                'deduction_started_at' => $cashAdvance->deduction_started_at ?: now(),
                'completed_at' => now(),
            ]);
        }
    }

    public function cashChargeDeductionForPayroll(int $employeeProfileId, PayrollPeriod $payrollPeriod): array
    {
        $cashCharge = CashCharge::where('employee_profile_id', $employeeProfileId)
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->first();

        if (!$cashCharge) {
            return ['id' => null, 'amount' => 0.0];
        }

        $totalBalance = (float) $cashCharge->amount;
        $installment = (float) ($cashCharge->installment_per_cutoff ?: ($totalBalance / 2));
        $previousDeductions = PayrollEntry::where('employee_profile_id', $employeeProfileId)
            ->where('cash_charge_id', $cashCharge->id)
            ->whereHas('payrollPeriod', function ($query) use ($payrollPeriod, $cashCharge) {
                $query->whereDate('end_date', '<', $payrollPeriod->start_date)
                    ->where('status', 'completed')
                    ->whereNotNull('approved_at')
                    ->whereNotNull('hr_approved_at')
                    ->whereNotNull('branch_approved_at')
                    ->where('admin_approval_stage', 'bh_approved')
                    ->whereNull('correction_stage');
            })
            ->sum('cash_charge_deduction');

        return [
            'id' => $cashCharge->id,
            'amount' => round(min($installment, max(0, $totalBalance - (float) $previousDeductions)), 2),
        ];
    }

    public function computePayrollForDTR(DTR $dtr)
    {
        $employeeProfile = $dtr->employeeProfile;

        if (!$employeeProfile) {
            return [
                'basic_pay' => 0.0,
                'overtime_pay' => 0.0,
                'days_worked' => 0,
                'days_absent' => 0,
                'late_deduction' => 0.0,
                'absent_deduction' => 0.0,
                'leave_deduction' => 0.0,
                'gross_pay' => 0.0,
                'sss_deduction' => 0.0,
                'philhealth_deduction' => 0.0,
                'pagibig_deduction' => 0.0,
                'withholding_tax' => 0.0,
                'total_deductions' => 0.0,
                'net_pay' => 0.0,
                'breakdown' => [
                    'daily_rate' => '0.00',
                    'basic_pay' => '0.00',
                    'overtime_pay' => '0.00',
                    'late_deduction' => '0.00',
                    'absent_deduction' => '0.00',
                    'leave_deduction' => '0.00',
                    'gross_pay' => '0.00',
                    'sss_deduction' => '0.00',
                    'philhealth_deduction' => '0.00',
                    'pagibig_deduction' => '0.00',
                    'withholding_tax' => '0.00',
                    'total_deductions' => '0.00',
                    'net_pay' => '0.00',
                ],
            ];
        }

        $calculation = $this->buildPayrollCalculation($dtr, $employeeProfile);
        return $calculation;
    }

    public function resolveBasicSalary($employeeProfile): float
    {
        if ($employeeProfile === null) {
            return 0.0;
        }

        $salary = null;

        if (is_object($employeeProfile)) {
            foreach (['basic_salary', 'salary', 'monthly_salary'] as $field) {
                if (isset($employeeProfile->{$field}) && is_numeric($employeeProfile->{$field}) && (float) $employeeProfile->{$field} > 0) {
                    $salary = $employeeProfile->{$field};
                    break;
                }
            }

            if ($salary === null && method_exists($employeeProfile, 'user') && $employeeProfile->user) {
                $userProfile = $employeeProfile->user->profile;
                foreach (['basic_salary', 'salary', 'monthly_salary'] as $field) {
                    if (isset($userProfile->{$field}) && is_numeric($userProfile->{$field}) && (float) $userProfile->{$field} > 0) {
                        $salary = $userProfile->{$field};
                        break;
                    }
                }
            }
        } elseif (is_array($employeeProfile)) {
            foreach (['basic_salary', 'salary', 'monthly_salary'] as $field) {
                if (array_key_exists($field, $employeeProfile) && $employeeProfile[$field] !== '') {
                    $salary = $employeeProfile[$field];
                    break;
                }
            }
        } elseif (is_numeric($employeeProfile)) {
            $salary = $employeeProfile;
        }

        if (is_string($salary)) {
            $salary = trim($salary);
        }

        return is_numeric($salary) ? (float) $salary : 0.0;
    }

    public function resolveBasicPay(float $monthlySalary, int $daysWorked): float
    {
        if ($monthlySalary <= 0) {
            return 0.0;
        }

        return (float) $monthlySalary;
    }

    public function calculateHalfDayDeduction(int $halfDayCount, float $dailyRate): float
    {
        return max(0, $halfDayCount) * max(0, $dailyRate) * 0.5;
    }

    public function calculateSuspensionPay(float $hours): float
    {
        return round(max(0, $hours) * self::SUSPENSION_HOURLY_RATE, 2);
    }

    private function buildPayrollCalculation(DTR $dtr, $employeeProfile)
    {
        // Calculate all components using the same DTR breakdown as the review screen.
        $breakdown = $dtr->getCalculationBreakdown();
        $daysWorked = (int) ($breakdown['days_present'] ?? 0);
        $halfDayCount = (int) ($breakdown['half_day_days'] ?? 0);
        $daysAbsent = (int) ($breakdown['days_absent'] ?? 0);
        $paidLeaveDays = (int) ($breakdown['paid_leave'] ?? 0);
        $leaveWithoutPayDays = (int) ($breakdown['leave_without_pay'] ?? 0);
        $overtimeHours = 0;
        $lateMinutes = $dtr->getTotalLateMinutes();

        // Always derive basic pay from the employee profile salary.
        $monthlySalary = $this->resolveBasicSalary($employeeProfile);
        $basicPay = $this->resolveBasicPay($monthlySalary, $daysWorked);
        $workingDays = max(1, $dtr->getWorkingDays());
        $dailyRate = $basicPay / $workingDays;
        $halfDayDeduction = $this->calculateHalfDayDeduction($halfDayCount, $dailyRate);

        // Calculate deductions
        $lateDeduction = $this->calculateLateDeduction($lateMinutes);
        $earlyOutMinutes = (int) ($breakdown['early_out_minutes'] ?? $dtr->getTotalEarlyOutMinutes());
        $earlyOutDeduction = $this->calculateEarlyOutDeduction($earlyOutMinutes);
        $absentDeduction = $this->calculateAbsentDeduction($daysAbsent, $dailyRate);
        $leaveWithoutPayDeduction = $this->calculateAbsentDeduction($leaveWithoutPayDays, $dailyRate);
        $leaveDeduction = $this->calculateLeaveDeduction($paidLeaveDays, $dailyRate);

        // Calculate overtime pay (25% premium on hourly rate)
        $hourlyRate = $dailyRate / 8;
        $overtimePay = $overtimeHours * $hourlyRate * 1.25;

        // Gross pay (before deductions)
        $grossPay = $basicPay + $overtimePay;

        // Calculate SSS, PhilHealth, Pag-ibig contributions
        $sssDeduction = $this->calculateSSS($monthlySalary);
        $philhealthDeduction = $this->calculatePhilHealth($monthlySalary);
        $pagibigDeduction = $this->calculatePagIBIG($monthlySalary);

        // Calculate withholding tax
        $taxableIncome = $grossPay - $sssDeduction - $philhealthDeduction - $pagibigDeduction;
        $withholdingTax = $this->calculateWithholdingTax($taxableIncome);

        // Total deductions
        $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $withholdingTax
              + $lateDeduction + $absentDeduction + $leaveWithoutPayDeduction + $halfDayDeduction;
            $totalDeductions += $earlyOutDeduction;

        // Net pay
        $netPay = $grossPay - $totalDeductions;

        $calculation = [
            'dtr_period' => $dtr->period_start->format('M d') . ' - ' . $dtr->period_end->format('M d, Y'),
            'daily_rate' => round($dailyRate, 2),
            'days_worked' => $daysWorked,
            'half_day_days' => $halfDayCount,
            'days_absent' => $daysAbsent,
            'approved_leaves' => $paidLeaveDays,
            'late_minutes' => $lateMinutes,
            'early_out_minutes' => $earlyOutMinutes,
            'overtime_hours' => round($overtimeHours, 2),
            'basic_pay' => round($basicPay, 2),
            'overtime_pay' => round($overtimePay, 2),
            'late_deduction' => round($lateDeduction, 2),
            'early_out_deduction' => round($earlyOutDeduction, 2),
            'absent_deduction' => round($absentDeduction, 2),
            'leave_without_pay_deduction' => round($leaveWithoutPayDeduction, 2),
            'half_day_deduction' => round($halfDayDeduction, 2),
            'leave_deduction' => round($leaveDeduction, 2),
            'gross_pay' => round($grossPay, 2),
            'sss_deduction' => round($sssDeduction, 2),
            'philhealth_deduction' => round($philhealthDeduction, 2),
            'pagibig_deduction' => round($pagibigDeduction, 2),
            'withholding_tax' => round($withholdingTax, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => round($netPay, 2),
            'breakdown' => [
                'daily_rate' => number_format($dailyRate, 2),
                'days_present' => $daysWorked,
                'half_day_days' => $halfDayCount,
                'days_absent' => $daysAbsent,
                'paid_leave' => $paidLeaveDays,
                'leave_without_pay' => $leaveWithoutPayDays,
                'basic_pay' => number_format($basicPay, 2),
                'overtime_pay' => number_format($overtimePay, 2),
                'late_deduction' => number_format($lateDeduction, 2),
                'early_out_deduction' => number_format($earlyOutDeduction, 2),
                'absent_deduction' => number_format($absentDeduction, 2),
                'leave_without_pay_deduction' => number_format($leaveWithoutPayDeduction, 2),
                'half_day_deduction' => number_format($halfDayDeduction, 2),
                'leave_deduction' => number_format($leaveDeduction, 2),
                'gross_pay' => number_format($grossPay, 2),
                'sss_deduction' => number_format($sssDeduction, 2),
                'philhealth_deduction' => number_format($philhealthDeduction, 2),
                'pagibig_deduction' => number_format($pagibigDeduction, 2),
                'withholding_tax' => number_format($withholdingTax, 2),
                'total_deductions' => number_format($totalDeductions, 2),
                'net_pay' => number_format($netPay, 2),
            ],
        ];

        return $calculation;
    }

    /**
     * Get approved leave days during DTR period
     */
    private function getApprovedLeaveDays($employeeProfileId, $periodStart, $periodEnd)
    {
        $approvedLeaves = LeaveRequest::where('employee_profile_id', $employeeProfileId)
            ->where('status', 'approved')
            ->where('is_absent', false)
            ->whereBetween('start_date', [$periodStart, $periodEnd])
            ->get();

        $leaveDays = 0;
        foreach ($approvedLeaves as $leave) {
            $start = max($leave->start_date, $periodStart);
            $end = min($leave->end_date, $periodEnd);
            
            $current = $start->copy();
            while ($current <= $end) {
                // Only count weekdays
                if ($current->dayOfWeek != 0 && $current->dayOfWeek != 6) {
                    $leaveDays++;
                }
                $current->addDay();
            }
        }

        return $leaveDays;
    }

    private function calculateLateDeduction($lateMinutes)
    {
        return max(0, (int) $lateMinutes);
    }

    /**
     * Calculate absent deduction
     */
    private function calculateAbsentDeduction($daysAbsent, $dailyRate)
    {
        if ($daysAbsent <= 0) {
            return 0;
        }

        return $dailyRate * $daysAbsent;
    }

    private function calculateEarlyOutDeduction($earlyOutMinutes)
    {
        return max(0, (int) $earlyOutMinutes);
    }

    /**
     * Calculate leave deduction (usually paid, so negative - i.e., add to gross)
     */
    private function calculateLeaveDeduction($approveLeaveDays, $dailyRate)
    {
        // Approved leaves are paid, so we add to gross pay
        return $dailyRate * $approveLeaveDays;
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

        return $sss ? $sss->employee_share : 0;
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
     * Calculate BIR Withholding Tax
     */
    private function calculateWithholdingTax($taxableIncome)
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
     * Process multiple DTRs for a payroll period
     */
    public function processPayrollPeriodFromDTRs(PayrollPeriod $payrollPeriod)
    {
        // Find all DTRs that fall within or overlap the payroll period
        $dtrs = DTR::whereIn('status', ['approved', 'pending_finance_head'])
            ->whereHas('employeeProfile', function ($query) use ($payrollPeriod) {
                $query->where('branch_id', $payrollPeriod->branch_id);
            })
            ->where('period_start', '<=', $payrollPeriod->end_date)
            ->where('period_end', '>=', $payrollPeriod->start_date)
            ->whereDoesntHave('payrollEntry', function ($query) use ($payrollPeriod) {
                $query->where('payroll_period_id', $payrollPeriod->id);
            })
            ->get();

        $createdCount = 0;
        foreach ($dtrs as $dtr) {
            $this->generatePayrollFromDTR($dtr, $payrollPeriod);
            $createdCount++;
        }

        return $createdCount;
    }
}
