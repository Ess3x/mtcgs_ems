<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DTR - <?php echo e($employeeProfile->employee_number); ?></title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
        .header { margin-bottom: 14px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { border: 0; padding: 0; text-align: center; vertical-align: middle; }
        .logo { display: block; width: 92px; height: 92px; object-fit: contain; margin: 0 auto 8px; }
        .company { font-size: 15px; font-weight: bold; }
        .title { font-size: 12px; margin-top: 4px; }
        .meta { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 5px; }
        th { background: #e5e7eb; text-align: center; }
        td.center { text-align: center; }
        .summary td { width: 25%; }
        .summary strong { display: block; font-size: 11px; margin-top: 3px; }
        .status { text-align: center; font-weight: bold; }
        .signature { page-break-inside: avoid; margin-top: 16px; text-align: center; }
        .signature-table { width: 220px; margin: 0 auto; border-collapse: collapse; }
        .signature-table td { border: 0; padding: 0; text-align: center; }
        .signature img { display: block; width: 180px; height: 54px; object-fit: contain; margin: 0 auto 3px; }
        .signature-line { width: 180px; border-top: 1px solid #111; margin: 30px auto 4px; }
        .signature-name { display: block; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; color: #6b7280; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <?php if($logo): ?>
                        <img class="logo" src="<?php echo e($logo); ?>" alt="MTCGS logo">
                    <?php endif; ?>
                    <div class="company">MOTHER THERESA COLEGIO GROUP OF SCHOOLS</div>
                    <div class="title">DAILY TIME RECORD</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Employee:</strong> <?php echo e($employeeProfile->first_name); ?> <?php echo e($employeeProfile->last_name); ?></td>
            <td><strong>Employee No.:</strong> <?php echo e($employeeProfile->employee_number); ?></td>
        </tr>
        <tr>
            <td><strong>Period:</strong> <?php echo e($dtr->period_start->format('M d, Y')); ?> - <?php echo e($dtr->period_end->format('M d, Y')); ?></td>
            <td><strong>Status:</strong> <?php echo e(ucfirst($dtr->status)); ?></td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>Total Hours<strong><?php echo e(number_format($stats['total_hours'], 2)); ?></strong></td>
            <td>Working Days<strong><?php echo e($stats['working_days']); ?></strong></td>
            <td>Days Present<strong><?php echo e($stats['days_present']); ?></strong></td>
            <td>Days Absent<strong><?php echo e($stats['days_absent']); ?></strong></td>
        </tr>
    </table>

    <table style="margin-top: 14px;">
        <thead>
            <tr>
                <th>Date</th>
                <th>AM In</th>
                <th>AM Out</th>
                <th>PM In</th>
                <th>PM Out</th>
                <th>Late (min)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $daysInPeriod; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ($log = $day['log'] ?? null); ?>
                <tr>
                    <td class="center"><?php echo e($day['date']->format('M d, Y')); ?></td>
                    <td class="center"><?php echo e($log?->am_in?->format('h:i A') ?? '--'); ?></td>
                    <td class="center"><?php echo e($log?->am_out?->format('h:i A') ?? '--'); ?></td>
                    <td class="center"><?php echo e($log?->pm_in?->format('h:i A') ?? '--'); ?></td>
                    <td class="center"><?php echo e($log?->pm_out?->format('h:i A') ?? '--'); ?></td>
                    <td class="center"><?php echo e($log?->late_minutes ?? 0); ?></td>
                    <td class="status"><?php echo e(ucfirst($day['status'] ?? 'N/A')); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <div class="signature">
        <table class="signature-table">
            <tr>
                <td>
                    <?php if($employeeSignature): ?>
                        <img src="<?php echo e($employeeSignature); ?>" alt="Employee e-signature">
                    <?php else: ?>
                        <div class="signature-line"></div>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="signature-name"><?php echo e($employeeProfile->first_name); ?> <?php echo e($employeeProfile->last_name); ?></span>
                    <span>Employee Signature</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">Generated <?php echo e(now()->format('M d, Y h:i A')); ?></div>
</body>
</html>
<?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\pdf\dtr.blade.php ENDPATH**/ ?>