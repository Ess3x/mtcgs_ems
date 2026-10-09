<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip - <?php echo e($entry->employee->employee_number); ?></title>
    <style>
        @page { margin: 10px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; margin: 0; padding: 10px; }
        .header { text-align: center; margin-bottom: 8px; }
        .payslip-logo { display: block; width: 58px; height: 58px; object-fit: contain; margin: 0 auto 4px; }
        .company-name { font-size: 14px; font-weight: bold; color: #2c3e50; }
        .payslip-title { font-size: 12px; margin-top: 2px; color: #3498db; }
        .info-section { margin-bottom: 8px; border: 1px solid #ddd; padding: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .net-pay { font-size: 11px; font-weight: bold; color: #27ae60; text-align: right; margin-top: 8px; padding-top: 5px; border-top: 2px solid #27ae60; }
        .footer { text-align: center; margin-top: 8px; font-size: 7px; color: #999; }
        .signature { margin-top: 18px; width: 100%; }
        .payslip-signatures { width: 100%; table-layout: fixed; margin-bottom: 4px; }
        .payslip-signatures td { width: 33.33%; border: none; padding: 0 8px; text-align: center; vertical-align: top; }
        .signature-slot { height: 42px; display: flex; align-items: flex-end; justify-content: center; }
        .signature-line { width: 150px; border-top: 1px solid #000; margin: 0 auto 5px; }
        .signature-image { display: block; width: 150px; height: 38px; object-fit: contain; margin: 0 auto 4px; }
        .signature-label { display: block; min-height: 12px; }
        .amount { font-weight: bold; }
    </style>
</head>
<body>
    <?php
        $breakdown = is_string($entry->payroll_breakdown)
            ? (json_decode($entry->payroll_breakdown, true) ?? [])
            : (array) ($entry->payroll_breakdown ?? []);
        $employeeName = trim((string) ($entry->employeeProfile?->first_name ?? $entry->employee?->first_name ?? '')) . ' ' . trim((string) ($entry->employeeProfile?->last_name ?? $entry->employee?->last_name ?? ''));
        $employeeNumber = $entry->employeeProfile?->employee_number ?? $entry->employee?->employee_number ?? 'N/A';
        $basicPay = is_numeric($entry->basic_pay ?? null) ? (float) $entry->basic_pay : 0;
        $grossPay = is_numeric($entry->gross_pay ?? null) ? (float) $entry->gross_pay : 0;
        $totalDeductions = is_numeric($entry->total_deductions ?? null) ? (float) $entry->total_deductions : 0;
        $netPay = is_numeric($entry->net_pay ?? null) ? (float) $entry->net_pay : 0;
        $lateDeduction = is_numeric($entry->late_deduction ?? null) ? (float) $entry->late_deduction : 0;
        $sss = is_numeric($entry->sss_contribution ?? null) ? (float) $entry->sss_contribution : 0;
        $philhealth = is_numeric($entry->philhealth_contribution ?? null) ? (float) $entry->philhealth_contribution : 0;
        $pagibig = is_numeric($entry->pagibig_contribution ?? null) ? (float) $entry->pagibig_contribution : 0;
        $withholdingTax = is_numeric($entry->withholding_tax ?? null) ? (float) $entry->withholding_tax : 0;
        $earlyOutDeduction = is_numeric($breakdown['early_out_deduction'] ?? null) ? (float) $breakdown['early_out_deduction'] : 0;
        $cashAdvanceDeduction = is_numeric($entry->cash_advance_deduction ?? null) ? (float) $entry->cash_advance_deduction : 0;
        $dailyRate = is_numeric($breakdown['daily_rate'] ?? null) ? (float) $breakdown['daily_rate'] : 0;
        $leaveWithoutPayDays = (int) ($breakdown['leave_without_pay'] ?? 0);
        $paidLeaveDays = (int) ($breakdown['paid_leave'] ?? 0);
        $leaveWithoutPayDeduction = $leaveWithoutPayDays * $dailyRate;
        $absentDeduction = (float) ($entry->absent_deduction ?? (($breakdown['days_absent'] ?? 0) * $dailyRate));
        $daysPresent = (int) ($breakdown['days_present'] ?? ($entry->days_present ?? 0));
        $daysAbsent = (int) ($breakdown['days_absent'] ?? ($entry->days_absent ?? 0));
        $lateMinutes = (float) ($breakdown['late_minutes'] ?? 0);
        $earlyOutMinutes = (float) ($breakdown['early_out_minutes'] ?? 0);
        $lateRatePerMinute = $lateMinutes > 0 ? $lateDeduction / $lateMinutes : 0;
        $totalDailyRate = is_numeric($breakdown['total_daily_rate'] ?? null)
            ? (float) $breakdown['total_daily_rate']
            : $daysPresent * $dailyRate;
        $paidLeaveRate = $paidLeaveDays * $dailyRate;
    ?>

    <div class="header">
        <?php if($logo ?? null): ?>
            <img class="payslip-logo" src="<?php echo e($logo); ?>" alt="MTCGS logo">
        <?php endif; ?>
        <div class="company-name">MOTHER THERESA COLEGIO GROUP OF SCHOOLS</div>
        <div class="payslip-title">EMPLOYEE PAYSLIP</div>
        <div>Pay Period: <?php echo e($entry->payrollPeriod->period_code); ?></div>
        <div>Payment Date: <?php echo e(date('F d, Y', strtotime($entry->payrollPeriod->payment_date))); ?></div>
    </div>

    <div class="info-section">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 50%;"><strong>Employee Name:</strong> <?php echo e($employeeName); ?></td>
                <td style="border: none; width: 50%;"><strong>Employee #:</strong> <?php echo e($employeeNumber); ?></td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"><strong>Position:</strong> <?php echo e($entry->employeeProfile?->position ?? $entry->employee?->position ?? 'N/A'); ?></td>
                <td style="border: none;"><strong>Branch:</strong> <?php echo e($entry->branch?->branch_name ?? $entry->branch?->name ?? 'N/A'); ?></td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"><strong>Pay Period:</strong> <?php echo e(date('M d, Y', strtotime($entry->payrollPeriod->start_date))); ?> - <?php echo e(date('M d, Y', strtotime($entry->payrollPeriod->end_date))); ?></td>
                <td style="border: none;"><strong>Rate:</strong> ₱<?php echo e(number_format($basicPay, 2)); ?>/semi-monthly</td>
             </tr>
        </table>
    </div>

    <table>
        <tr><th colspan="3">ATTENDANCE &amp; RATE SUMMARY</th></tr>
        <tr>
            <td><strong>Days Present:</strong> <?php echo e($daysPresent); ?></td>
            <td><strong>Days Absent:</strong> <?php echo e($daysAbsent); ?></td>
            <td><strong>Paid Leave:</strong> <?php echo e($paidLeaveDays); ?></td>
        </tr>
        <tr>
            <td><strong>Late Minutes:</strong> <?php echo e(number_format($lateMinutes, 0)); ?></td>
            <td><strong>Early Out Minutes:</strong> <?php echo e(number_format($earlyOutMinutes, 0)); ?></td>
            <td><strong>Leave Without Pay:</strong> <?php echo e($leaveWithoutPayDays); ?></td>
        </tr>
        <tr>
            <td><strong>Daily Rate:</strong> ₱<?php echo e(number_format($dailyRate, 2)); ?></td>
            <td><strong>Late Rate / Minute:</strong> ₱<?php echo e(number_format($lateRatePerMinute, 2)); ?></td>
            <td></td>
        </tr>
    </table>

    <table>
        <tr><th colspan="2">EARNINGS</th><th colspan="2">DEDUCTIONS</th></tr>
        <tr>
            <td width="40%">Basic Pay</td>
            <td width="10%" class="amount" style="text-align: right;">₱<?php echo e(number_format($basicPay, 2)); ?></td>
            <td width="40%">SSS Contribution</td>
            <td width="10%" class="amount" style="text-align: right;">₱<?php echo e(number_format($sss, 2)); ?></td>
        </tr>
        <tr>
            <td>Total Daily Rate</td>
            <td class="amount" style="text-align: right;">₱<?php echo e(number_format($totalDailyRate, 2)); ?></td>
            <td>PhilHealth Contribution</td>
            <td class="amount" style="text-align: right;">₱<?php echo e(number_format($philhealth, 2)); ?></td>
        </tr>
        <tr>
            <td>Paid Leave</td>
            <td class="amount" style="text-align: right;">₱<?php echo e(number_format($paidLeaveDays * $dailyRate, 2)); ?></td>
            <td>Late Deduction</td>
            <td class="amount" style="text-align: right;">-₱<?php echo e(number_format($lateDeduction, 2)); ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: right;"></td>
            <td>Pag-IBIG Contribution</td>
            <td class="amount" style="text-align: right;">₱<?php echo e(number_format($pagibig, 2)); ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: right;"></td>
            <td>Withholding Tax</td>
            <td class="amount" style="text-align: right;">₱<?php echo e(number_format($withholdingTax, 2)); ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: right;"></td>
            <td>Early Out Deduction</td>
            <td class="amount" style="text-align: right;">₱<?php echo e(number_format($earlyOutDeduction, 2)); ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: right;"></td>
            <td>Cash Advance Deduction</td>
            <td class="amount" style="text-align: right;">₱<?php echo e(number_format($cashAdvanceDeduction, 2)); ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: right;"></td>
            <td>Absent Deduction</td>
            <td class="amount" style="text-align: right;">₱<?php echo e(number_format($absentDeduction, 2)); ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: right;"></td>
            <td>Leave Without Pay</td>
            <td class="amount" style="text-align: right;">₱<?php echo e(number_format($leaveWithoutPayDeduction, 2)); ?></td>
        </tr>
        <tr class="total-row">
            <td><strong>GROSS PAY</strong></td>
            <td style="text-align: right;"><strong>₱<?php echo e(number_format($grossPay, 2)); ?></strong></td>
            <td><strong>TOTAL DEDUCTIONS</strong></td>
            <td style="text-align: right;"><strong>₱<?php echo e(number_format($totalDeductions, 2)); ?></strong></td>
        </tr>
    </table>

    <div class="net-pay">
        <strong>NET PAY: ₱<?php echo e(number_format($netPay, 2)); ?></strong>
    </div>

    <div class="signature">
        <table class="payslip-signatures">
            <tr style="border: none;">
                <td style="border: none; width: 33.33%; text-align: center;">
                    <div class="signature-slot">
                        <?php if($employeeSignature): ?>
                            <img class="signature-image" src="<?php echo e($employeeSignature); ?>" alt="Employee e-signature">
                        <?php else: ?>
                            <div class="signature-line"></div>
                        <?php endif; ?>
                    </div>
                    <small class="signature-label">Employee Signature</small>
                 </td>
                <td style="border: none; width: 33.33%; text-align: center;">
                    <div class="signature-slot">
                        <?php if($financeOfficerSignature): ?>
                            <img class="signature-image" src="<?php echo e($financeOfficerSignature); ?>" alt="Finance Officer e-signature">
                        <?php else: ?>
                            <div class="signature-line"></div>
                        <?php endif; ?>
                    </div>
                    <small class="signature-label">Finance Officer Signature</small>
                 </td>
                <td style="border: none; width: 33.33%; text-align: center;">
                    <div class="signature-slot">
                        <?php if($branchHeadSignature): ?>
                            <img class="signature-image" src="<?php echo e($branchHeadSignature); ?>" alt="Branch Head e-signature">
                        <?php else: ?>
                            <div class="signature-line"></div>
                        <?php endif; ?>
                    </div>
                    <small class="signature-label">Branch Head Signature</small>
                 </td>
             </tr>
        </table>
    </div>

    <div class="footer">
        This is a system-generated payslip. For inquiries, please contact Finance Department.<br>
        MTCGS-EMS v2.0 | Generated on <?php echo e(now()->format('F d, Y h:i A')); ?>

    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\pdf\payslip.blade.php ENDPATH**/ ?>