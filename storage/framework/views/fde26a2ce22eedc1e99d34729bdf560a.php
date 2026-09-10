<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Approved</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #28a745;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .leave-details {
            background: white;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .label {
            font-weight: bold;
            color: #28a745;
        }
        .badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            background: #28a745;
            color: white;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Leave Request Approved</h1>
        </div>

        <div class="content">
            <p>Dear <?php echo e($employeeProfile->first_name); ?> <?php echo e($employeeProfile->last_name); ?>,</p>

            <p>Good news! Your leave request has been <span class="badge">APPROVED BY THE SYSTEM ADMINISTRATOR</span></p>

            <?php if($leaveRequest->is_absent): ?>
                <p style="color: #b02a37;"><strong>Notice:</strong> Because you are currently classified as New Hire, this approved leave is recorded as absence and does not use leave credits.</p>
            <?php endif; ?>

            <div class="leave-details">
                <h3 style="margin-top: 0; color: #28a745;">Leave Details</h3>
                
                <div class="detail-row">
                    <span class="label">Leave Type:</span><br>
                    <?php echo e(ucfirst($leaveRequest->leave_type)); ?> Leave
                </div>

                <div class="detail-row">
                    <span class="label">From Date:</span><br>
                    <?php echo e($leaveRequest->start_date->format('F d, Y (l)')); ?>

                </div>

                <div class="detail-row">
                    <span class="label">To Date:</span><br>
                    <?php echo e($leaveRequest->end_date->format('F d, Y (l)')); ?>

                </div>

                <div class="detail-row">
                    <span class="label">Total Days:</span><br>
                    <strong><?php echo e($leaveRequest->total_days); ?> day(s)</strong>
                </div>

                <div class="detail-row">
                    <span class="label">Reason:</span><br>
                    <?php echo e($leaveRequest->reason); ?>

                </div>

                <div class="detail-row">
                    <span class="label">Approved On:</span><br>
                    <?php echo e($leaveRequest->approved_at->format('F d, Y h:i A')); ?>

                </div>
            </div>

            <p>Your leave has been successfully approved and recorded in the system. <?php if(!$leaveRequest->is_absent): ?> You're all set to take your well-deserved break! <?php endif; ?></p>

            <p>If you have any questions about your approved leave, please contact your administrator or HR department.</p>

            <p>Best regards,<br>
            <strong>MTCGS-EMS Administration Team</strong></p>
        </div>

        <div class="footer">
            <p>© <?php echo e(date('Y')); ?> Mother Theresa Colegio Group of Schools | Employee Management System</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views/emails/leave-approved.blade.php ENDPATH**/ ?>