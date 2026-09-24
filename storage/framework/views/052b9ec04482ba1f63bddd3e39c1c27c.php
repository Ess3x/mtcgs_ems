<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Status Change Pending Approval</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background: #f8fafc; margin: 0; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
        <div style="background: #1d4ed8; color: #ffffff; padding: 20px 24px; font-size: 20px; font-weight: bold;">
            Employee Status Change Pending Approval
        </div>

        <div style="padding: 24px;">
            <p>Hello System Administrator,</p>

            <p>
                <strong><?php echo e($requestedBy->name); ?></strong> submitted a status change request for
                <strong><?php echo e($employee->first_name); ?> <?php echo e($employee->last_name); ?></strong>.
            </p>

            <p>
                <strong>Previous status:</strong> <?php echo e($previousStatus ?? 'New Hire'); ?><br>
                <strong>New status:</strong> <?php echo e($newStatus); ?>

            </p>

            <p>Please review and approve the status change from the System Administrator dashboard.</p>

            <p>Thank you,<br>MTCGS-EMS</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\emails\employee-status-change-pending-approval.blade.php ENDPATH**/ ?>