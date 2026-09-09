<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Register - MTCGS EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.08);
        }
        .btn-register {
            background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);
            border: none;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #1e293b 0%, #1d4ed8 100%);
        }
        .form-label {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="col-lg-8">
            <div class="card overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-6 d-none d-md-flex bg-primary text-white align-items-center justify-content-center p-4">
                        <div>
                            <h3 class="fw-bold">Join MTCGS EMS</h3>
                            <p class="text-white-75">Register your employee account and request admin approval before logging in.</p>
                            <i class="fas fa-user-plus fa-4x mt-4"></i>
                        </div>
                    </div>
                    <div class="col-md-6 p-4">
                        <div class="mb-4 text-center">
                            <a href="<?php echo e(url('/')); ?>" class="text-decoration-none text-dark">
                                <h4 class="fw-bold">MTCGS EMS</h4>
                            </a>
                            <p class="text-muted">Create your account request below.</p>
                        </div>

                        <?php if($errors->any()): ?>
                            <div class="alert alert-danger">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div><?php echo e($error); ?></div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo e(route('register')); ?>">
                            <?php echo csrf_field(); ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo e(old('first_name')); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo e(old('last_name')); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo e(old('email')); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Branch</label>
                                    <select name="branch_id" class="form-select" required>
                                        <option value="">Choose branch</option>
                                        <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($branch->id); ?>" <?php echo e(old('branch_id') == $branch->id ? 'selected' : ''); ?>><?php echo e($branch->branch_name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Position</label>
                                    <input type="text" name="position" class="form-control" value="<?php echo e(old('position')); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="password_confirmation" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control" value="<?php echo e(old('contact_number')); ?>">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-register btn-lg w-100 text-white">
                                        <i class="fas fa-user-plus me-2"></i>Submit Registration Request
                                    </button>
                                </div>
                            </div>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="<?php echo e(route('login')); ?>" class="link-secondary">Already have an account? Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\auth\register.blade.php ENDPATH**/ ?>