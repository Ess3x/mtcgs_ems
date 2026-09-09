<!DOCTYPE html>
<html>
<body>
    <h2>Employment Status Updated</h2>
    <p>Dear <?php echo e($employeeProfile->first_name); ?>,</p>
    <p>Your employment status has been updated in MTCGS-EMS.</p>
    <p><strong>Previous status:</strong> <?php echo e($previousStatus); ?></p>
    <p><strong>New status:</strong> <?php echo e($newStatus); ?></p>
    <p>Your leave credits will follow your new employment status.</p>
    <p>Thank you.</p>
</body>
</html><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\emails\employee-status-changed.blade.php ENDPATH**/ ?>