
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your MTCGS-EMS Account Credentials</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #8A2BE2 0%, #4B0082 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .credentials-box {
            background: white;
            border: 2px solid #8A2BE2;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .credential-item {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .label {
            font-weight: bold;
            color: #8A2BE2;
        }
        .value {
            font-family: monospace;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 3px;
            margin-top: 5px;
            word-break: break-all;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
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
    <div class="header">
        <h1>MTCGS-EMS</h1>
        <h2>Your Account Has Been Created</h2>
    </div>

    <div class="content">
        <p>Dear <?php echo e($user->name); ?>,</p>

        <p>Welcome to the Mother Theresa Colegio Group of Schools Employee Management System!</p>

        <p>Your account has been successfully created. Below are your login credentials:</p>

        <div class="credentials-box">
            <h3 style="margin-top: 0; color: #8A2BE2;">Account Information</h3>

            <div class="credential-item">
                <div class="label">Role:</div>
                <div><?php echo e(ucfirst(str_replace('_', ' ', $role))); ?></div>
            </div>

            <div class="credential-item">
                <div class="label">Email/Username:</div>
                <div class="value"><?php echo e($user->email); ?></div>
            </div>

            <div class="credential-item">
                <div class="label">Password:</div>
                <div class="value"><?php echo e($password); ?></div>
            </div>
        </div>

        <div class="warning">
            <strong>⚠️ Important Security Notice:</strong><br>
            Please change your password immediately after your first login for security purposes.
            You can change your password in your profile settings.
        </div>

        <p><strong>How to Login:</strong></p>
        <ol>
            <li>Visit the MTCGS-EMS login page</li>
            <li>Select your branch (<?php echo e($user->branch->branch_name ?? 'N/A'); ?>)</li>
            <li>Enter your email and the password above</li>
            <li>Click "Login"</li>
             <li>You Can now Talk to the Branch Head to Enroll your Fingerprint</li>
        </ol>

        <p>If you have any questions or need assistance, please contact your administrator.</p>

        <p>Best regards,<br>
        MTCGS-EMS Administration Team</p>
    </div>

    <div class="footer">
        <p>© <?php echo e(date('Y')); ?> Mother Theresa Colegio Group of Schools | Employee Management System</p>
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\emails\account-credentials.blade.php ENDPATH**/ ?>