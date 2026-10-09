<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payroll Report - <?php echo e($period->period_code); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 18px; }
        .company { font-size: 16px; font-weight: bold; }
        .title { font-size: 13px; margin-top: 5px; }
        .period { margin-top: 4px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; }
        th { background: #e5e7eb; text-align: left; font-size: 9px; }
        td.amount { text-align: right; white-space: nowrap; }
        td.status { text-align: center; }
        .total td { background: #f3f4f6; font-weight: bold; }
        .approval { margin-top: 28px; border: 1px solid #cbd5e1; padding: 12px; }
        .approval-title { font-weight: bold; margin-bottom: 15px; }
        .signature { display: inline-block; width: 45%; margin-right: 4%; vertical-align: top; }
        .approval .signature { text-align: center; }
        .signature-line { border-bottom: 1px solid #1f2937; height: 22px; margin-bottom: 4px; }
        .signature-image { display: block; height: 22px; max-width: 150px; object-fit: contain; margin: 4px auto 0; }
        .approval-grid { width: 100%; border-collapse: collapse; }
        .approval-grid td { border: 0; width: 50%; padding: 0 8px; text-align: center; vertical-align: top; }
        .signer-table { width: 190px; border-collapse: collapse; margin-left: 0; }
        .signer-table td { width: 100%; padding: 0; text-align: center; vertical-align: middle; }
        .approved-badge { display: inline-block; background: #198754; color: #fff; border-radius: 4px; padding: 5px 12px; font-weight: bold; }
        .footer { margin-top: 18px; text-align: right; color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
    <?php ($isApprovedPayroll = in_array($period->status, ['approved', 'completed'], true)); ?>
    <div class="header">
        <div class="company">MOTHER THERESA COLEGIO GROUP OF SCHOOLS</div>
        <div class="title">PAYROLL REPORT</div>
        <div class="period"><?php echo e($period->period_code); ?> | <?php echo e($period->branch?->branch_name ?? 'N/A'); ?> | <?php echo e($period->start_date->format('M d, Y')); ?> - <?php echo e($period->end_date->format('M d, Y')); ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Employee Number</th>
                <th style="text-align:right">Basic Pay</th>
                <th style="text-align:right">Gross Pay</th>
                <th style="text-align:right">Total Deductions</th>
                <th style="text-align:right">Net Pay</th>
                <th style="text-align:center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e(trim(($entry->employee->first_name ?? '') . ' ' . ($entry->employee->last_name ?? ''))); ?></td>
                    <td><?php echo e($entry->employee->employee_number ?? 'N/A'); ?></td>
                    <td class="amount">₱<?php echo e(number_format($entry->basic_pay, 2)); ?></td>
                    <td class="amount">₱<?php echo e(number_format($entry->gross_pay, 2)); ?></td>
                    <td class="amount">₱<?php echo e(number_format($entry->total_deductions, 2)); ?></td>
                    <td class="amount">₱<?php echo e(number_format($entry->net_pay, 2)); ?></td>
                    <td class="status"><?php echo e($isApprovedPayroll ? ($period->admin_approval_stage === 'hr_fd' ? 'Approved HR/FD' : 'Finance Head Approved') : ucfirst($period->status)); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" style="text-align:center">No payroll entries found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <?php if($entries->isNotEmpty()): ?>
            <tfoot>
                <tr class="total">
                    <td colspan="2">TOTAL</td>
                    <td class="amount">₱<?php echo e(number_format($entries->sum('basic_pay'), 2)); ?></td>
                    <td class="amount">₱<?php echo e(number_format($entries->sum('gross_pay'), 2)); ?></td>
                    <td class="amount">₱<?php echo e(number_format($entries->sum('total_deductions'), 2)); ?></td>
                    <td class="amount">₱<?php echo e(number_format($entries->sum('net_pay'), 2)); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>

    <?php if($isApprovedPayroll): ?>
        <div class="approval">
            <div class="approval-title">Finance Head Approval</div>
            <table class="approval-grid">
                <tr>
                    <td>
                        <table class="signer-table">
                            <tr>
                                <td>
                                    <?php if($financeHeadSignature): ?>
                                        <img class="signature-image" src="<?php echo e($financeHeadSignature); ?>" alt="Finance Head e-signature">
                                    <?php else: ?>
                                        <div class="signature-line"></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo e($period->approvedBy?->name ?? 'Finance Head'); ?></strong></td>
                            </tr>
                            <tr>
                                <td><span class="approved-badge">APPROVED</span></td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <strong><?php echo e($approvalDate?->format('M d, Y h:i A') ?? 'Pending approval date'); ?></strong><br>
                        <span>Approval Date</span>
                    </td>
                </tr>
            </table>
        </div>
        <?php if($period->hr_approved_at): ?>
            <div class="approval">
                <div class="approval-title">HR Approval</div>
                <table class="approval-grid">
                    <tr>
                        <td>
                            <table class="signer-table">
                                <tr>
                                    <td>
                                        <?php if($hrSignature): ?>
                                            <img class="signature-image" src="<?php echo e($hrSignature); ?>" alt="HR e-signature">
                                        <?php else: ?>
                                            <div class="signature-line"></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo e($period->hrApprovedBy?->name ?? 'HR'); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><span class="approved-badge">APPROVED</span></td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <div class="signature-line"></div>
                            <strong><?php echo e($hrApprovalDate?->format('M d, Y h:i A') ?? 'Pending approval date'); ?></strong><br>
                            <span>Approval Date</span>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>
        <?php if($period->branch_approved_at): ?>
            <div class="approval">
                <div class="approval-title">Branch Head Approval</div>
                <table class="approval-grid">
                    <tr>
                        <td>
                            <table class="signer-table">
                                <tr>
                                    <td>
                                        <?php if($branchSignature): ?>
                                            <img class="signature-image" src="<?php echo e($branchSignature); ?>" alt="Branch Head e-signature">
                                        <?php else: ?>
                                            <div class="signature-line"></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo e($period->branchApprovedBy?->name ?? 'Branch Head'); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><span class="approved-badge">APPROVED</span></td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <div class="signature-line"></div>
                            <strong><?php echo e($period->branch_approved_at?->format('M d, Y h:i A') ?? 'Pending approval date'); ?></strong><br>
                            <span>Approval Date</span>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="footer">Generated <?php echo e(now()->format('M d, Y h:i A')); ?></div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\pdf\payroll-report.blade.php ENDPATH**/ ?>