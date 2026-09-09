<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h3 class="mb-3">Forgot Password</h3>
                        <?php if(session('status')): ?>
                            <div class="alert alert-success"><?php echo e(session('status')); ?></div>
                        <?php endif; ?>
                        <?php if($errors->any()): ?>
                            <div class="alert alert-danger"><?php echo e($errors->first()); ?></div>
                        <?php endif; ?>
                        <form method="POST" action="<?php echo e(route('password.email')); ?>">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input id="email" type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="<?php echo e(route('login')); ?>">Back to login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\auth\forgot-password.blade.php ENDPATH**/ ?>