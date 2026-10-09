<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your MTCGS-EMS Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
        }
        .wrapper {
            max-width: 640px;
            margin: 0 auto;
            padding: 24px 16px;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 12px 28px rgba(17, 24, 39, 0.08);
        }
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            padding: 28px 24px;
            text-align: center;
        }
        .logo {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 50%;
            background: #fff;
            padding: 6px;
            display: block;
            margin: 0 auto 12px;
        }
        .brand {
            margin: 0;
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .content {
            padding: 28px 28px 20px;
            line-height: 1.7;
            color: #1f2937;
        }
        .button {
            display: inline-block;
            margin: 18px 0 10px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            border-radius: 10px;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
        }
        .meta {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 22px;
            color: #475569;
        }
        .footer {
            padding: 18px 24px 28px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <img src="<?php echo e(asset('images/logo.jpg')); ?>" alt="MTCGS Logo" class="logo" onerror="this.style.display='none'">
                <p class="brand">MTCGS</p>
            </div>

            <div class="content">
                <p>Hello <?php echo e($user->name ?? 'there'); ?>,</p>

                <p>We received a request to reset your password for your MTCGS-EMS account.</p>

                <p>Click the button below to choose a new password:</p>

                <p style="margin: 0;">
                    <a href="<?php echo e($resetUrl); ?>" class="button">Reset Password</a>
                </p>

                <p>If the button does not work, copy and paste this URL into your browser:</p>
                <p style="word-break: break-all; color: #4f46e5;"><?php echo e($resetUrl); ?></p>

                <div class="meta">
                    This password reset link will expire in <?php echo e($expiresIn); ?> minutes.
                    If you did not request a password reset, you can safely ignore this email.
                </div>
            </div>

            <div class="footer">
                © <?php echo e(date('Y')); ?> Mother Theresa Colegio Group of Schools<br>
                Employee Management System
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\emails\password-reset.blade.php ENDPATH**/ ?>