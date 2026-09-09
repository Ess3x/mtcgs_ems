<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expired - MTCGS EMS</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f3f4ff; color: #1f2937; font-family: Arial, sans-serif; }
        .box { width: min(92%, 460px); padding: 2rem; text-align: center; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 12px 30px rgba(15, 23, 42, .1); }
        h1 { margin: 0 0 .75rem; color: #6b21a8; font-size: 1.5rem; }
        p { color: #6b7280; line-height: 1.5; }
        a { display: inline-block; margin-top: 1rem; padding: .65rem 1rem; border-radius: 6px; background: #7e22ce; color: #fff; text-decoration: none; }
    </style>
</head>
<body>
    <main class="box">
        <h1>Your session has expired</h1>
        <p>Please return to the previous page and submit the form again.</p>
        <a href="<?php echo e(url('/dashboard')); ?>">Back to Dashboard</a>
    </main>
</body>
</html>
<?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\errors\419.blade.php ENDPATH**/ ?>