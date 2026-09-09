<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Officer Change Pending Approval</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background: #f8fafc; margin: 0; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
        <div style="background: #7c3aed; color: #ffffff; padding: 20px 24px; font-size: 20px; font-weight: bold;">
            Finance Officer Change Pending Approval
        </div>

        <div style="padding: 24px;">
            <p>Hello System Administrator,</p>

            <p>
                <strong><?php echo e($requestedBy->name); ?></strong> submitted a change request for the Finance Officer profile of
                <strong><?php echo e($financeProfile->first_name); ?> <?php echo e($financeProfile->last_name); ?></strong>.
            </p>

            <p>The following updates are awaiting your approval:</p>

            <ul>
                <li><strong>First Name:</strong> <?php echo e($changes['first_name'] ?? $financeProfile->first_name); ?></li>
                <li><strong>Last Name:</strong> <?php echo e($changes['last_name'] ?? $financeProfile->last_name); ?></li>
                <li><strong>Position:</strong> <?php echo e($changes['position'] ?? $financeProfile->position); ?></li>
                <li><strong>Email:</strong> <?php echo e($changes['email'] ?? $financeProfile->user?->email); ?></li>
                <li><strong>Status:</strong> <?php echo e($changes['status'] ?? $financeProfile->status); ?></li>
                <li><strong>Date Hired:</strong> <?php echo e($changes['date_hired'] ?? $financeProfile->date_hired); ?></li>
                <li><strong>Can Process Payroll:</strong> <?php echo e(($changes['can_process_payroll'] ?? $financeProfile->can_process_payroll) ? 'Yes' : 'No'); ?></li>
            </ul>

            <p>Please review and approve or reject the pending change in the System Administrator dashboard.</p>

            <p>Thank you,<br>MTCGS-EMS</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\emails\finance-officer-change-pending-approval.blade.php ENDPATH**/ ?>