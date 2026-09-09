<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulk Payslips</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .payslip { page-break-after: always; margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; }
        .header { text-align: center; margin-bottom: 15px; }
        .company-name { font-size: 14px; font-weight: bold; }
        .payslip-title { font-size: 12px; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; font-size: 10px; }
        th { background-color: #f2f2f2; }
        .net-pay { font-size: 12px; font-weight: bold; text-align: right; margin-top: 10px; }
    </style>
</head>
<body>
    <?php $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="payslip">
        <div class="header">
            <div class="company-name">MOTHER THERESA COLEGIO GROUP OF SCHOOLS</div>
            <div class="payslip-title">EMPLOYEE PAYSLIP</div>
            <div>Period: <?php echo e($entry->payrollPeriod->period_code ?? 'N/A'); ?></div>
        </div>

        <table>
            <tr><td width="50%"><strong>Employee:</strong> <?php echo e(optional($entry->employeeProfile)->first_name ?? ''); ?> <?php echo e(optional($entry->employeeProfile)->last_name ?? ''); ?></td>
                <td width="50%"><strong>Employee #:</strong> <?php echo e(optional($entry->employeeProfile)->employee_number ?? 'N/A'); ?></td></tr>
            <tr><td><strong>Position:</strong> <?php echo e(optional($entry->employeeProfile)->position ?? 'N/A'); ?></td>
                <td><strong>Branch:</strong> <?php echo e(optional($entry->branch)->branch_name ?? 'N/A'); ?></td></tr>
        </table>

        <table>
            <tr><th>EARNINGS</th><th>Amount</th><th>DEDUCTIONS</th><th>Amount</th></tr>
            <tr><td>Basic Pay</td><td>₱<?php echo e(number_format($entry->basic_pay, 2)); ?></td><td>SSS</td><td>₱<?php echo e(number_format($entry->sss_contribution, 2)); ?></td></tr>
            <tr><td>Overtime Pay</td><td>₱<?php echo e(number_format($entry->overtime_pay, 2)); ?></td><td>PhilHealth</td><td>₱<?php echo e(number_format($entry->philhealth_contribution, 2)); ?></td></tr>
            <tr><td></td><td></td><td>Pag-IBIG</td><td>₱<?php echo e(number_format($entry->pagibig_contribution, 2)); ?></td></tr>
            <tr><td></td><td></td><td>Withholding Tax</td><td>₱<?php echo e(number_format($entry->withholding_tax, 2)); ?></td></tr>
            <tr class="total-row"><td><strong>GROSS PAY</strong></td><td><strong>₱<?php echo e(number_format($entry->gross_pay, 2)); ?></strong></td>
                <td><strong>TOTAL DEDUCTIONS</strong></td><td><strong>₱<?php echo e(number_format($entry->total_deductions, 2)); ?></strong></td></tr>
        </table>

        <div class="net-pay"><strong>NET PAY: ₱<?php echo e(number_format($entry->net_pay, 2)); ?></strong></div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</body>
</html>
<?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\pdf\bulk-payslips.blade.php ENDPATH**/ ?>