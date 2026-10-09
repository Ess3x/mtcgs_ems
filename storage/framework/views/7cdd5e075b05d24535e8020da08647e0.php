<?php $__env->startSection('title', 'Manage Admin Permissions'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h2><i class="fas fa-lock-open me-2"></i> Manage Admin Permissions</h2>
                    <p class="mb-0">Grant or revoke ID verification authority to administrators</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Administrators
                    </h5>
                    <span class="badge bg-info"><?php echo e($admins->count()); ?> Admin(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Admin Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Branch</th>
                                <th>Verify IDs</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $admins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($admin->first_name); ?> <?php echo e($admin->last_name); ?></strong>
                                        <?php if($admin->user->admin_type === 'super_admin'): ?>
                                            <span class="badge bg-danger ms-2">Super Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($admin->user->email); ?></td>
                                    <td><?php echo e($admin->position ?? 'N/A'); ?></td>
                                    <td><?php echo e(optional($admin->branch)->name ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if($admin->can_verify_ids): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Authorized
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times"></i> Not Authorized
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($admin->user->admin_type !== 'super_admin'): ?>
                                            <?php if($admin->can_verify_ids): ?>
                                                <form action="<?php echo e(route('admin.permissions.revoke', $admin->id)); ?>" method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to revoke ID verification permission?')">
                                                        <i class="fas fa-ban"></i> Revoke
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="<?php echo e(route('admin.permissions.grant', $admin->id)); ?>" method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to grant ID verification permission?')">
                                                        <i class="fas fa-check"></i> Grant
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Super Admin</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No administrators found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\permissions\index.blade.php ENDPATH**/ ?>