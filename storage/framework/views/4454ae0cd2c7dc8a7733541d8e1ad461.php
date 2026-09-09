<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip Submitted</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Dear <?php echo e($employeeName); ?>,</p>

    <p>Your payslip for pay period <strong><?php echo e($periodCode); ?></strong> is attached to this email.</p>

    <p>Please review the attached PDF and contact the Finance Department if you have any questions.</p>

    <p>Best regards,<br>
    MTCGS Finance Department</p>
</body>
</html>
<?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\emails\payslip-submitted.blade.php ENDPATH**/ ?>