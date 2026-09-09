<?php $__env->startSection('title', 'View User'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo e(ucfirst($role)); ?> Details
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Employee Number:</div>
                        <div class="col-md-8"><?php echo e($userData->employee_number); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Full Name:</div>
                        <div class="col-md-8"><?php echo e($userData->first_name); ?> <?php echo e($userData->last_name); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Email:</div>
                        <div class="col-md-8"><?php echo e($userData->user->email ?? 'N/A'); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Position:</div>
                        <div class="col-md-8"><?php echo e($userData->position ?? 'N/A'); ?></div>
                    </div>
                    
                    <?php if($role === 'employee'): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Branch:</div>
                            <div class="col-md-8"><?php echo e($userData->branch->branch_name ?? 'N/A'); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Basic Salary:</div>
                            <div class="col-md-8">₱<?php echo e(number_format($userData->basic_salary ?? 0, 2)); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Fingerprint Registered:</div>
                            <div class="col-md-8">
                                <?php if($userData->is_fingerprint_registered): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($role === 'finance'): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Can Process Payroll:</div>
                            <div class="col-md-8">
                                <?php if($userData->can_process_payroll): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($role === 'admin'): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Admin Level:</div>
                            <div class="col-md-8">
                                <?php if($userData->admin_level == 'super_admin'): ?>
                                    <span class="badge bg-danger">Super Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Admin</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Status:</div>
                        <div class="col-md-8">
                            <?php if($userData->user && $userData->user->is_active): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Date Hired:</div>
                        <div class="col-md-8"><?php echo e(date('F d, Y', strtotime($userData->date_hired))); ?></div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo e(route('admin.user-management')); ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <a href="<?php echo e(route('admin.user-edit', ['role' => $role, 'id' => $userData->id])); ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\user-view.blade.php ENDPATH**/ ?>