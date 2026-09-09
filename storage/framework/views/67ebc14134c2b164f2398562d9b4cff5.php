

<?php $__env->startSection('title', 'Branch Admin Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Branch Admin Management</h3>
            <p class="text-muted mb-0">Manage branch-specific admin accounts and access control.</p>
        </div>
        <a href="<?php echo e(route('admin.branch-heads.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Create Branch Admin
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Branch Admins</div>
                    <div class="fs-3 fw-bold"><?php echo e($totalBranchHeads); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Branch Admin List</h5>
        </div>
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-hover table-sm" style="min-width: 900px;">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Employee #</th>
                        <th>Branch</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $branchHeads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $head): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <strong><?php echo e($head->first_name); ?> <?php echo e($head->last_name); ?></strong><br>
                            <small class="text-muted"><?php echo e($head->position); ?></small>
                        </td>
                        <td><?php echo e($head->user->email); ?></td>
                        <td><?php echo e($head->employee_number); ?></td>
                        <td><span class="badge bg-secondary"><?php echo e($head->branch->branch_name ?? 'N/A'); ?></span></td>
                        <td><?php echo e($head->contact_number ?? 'N/A'); ?></td>
                        <td>
                            <?php if($head->user->is_active): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo e(route('admin.branch-heads.edit', $head->id)); ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="<?php echo e(route('admin.branch-heads.destroy', $head->id)); ?>" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Delete this branch head account?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="text-center py-4">No branch admins found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($branchHeads instanceof \Illuminate\Contracts\Pagination\Paginator || $branchHeads instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator): ?>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing <strong><?php echo e($branchHeads->count()); ?></strong> result<?php echo e($branchHeads->count() != 1 ? 's' : ''); ?>

                    </small>
                    <nav>
                        <?php echo e($branchHeads->links('pagination::bootstrap-4')); ?>

                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\branch-heads\index.blade.php ENDPATH**/ ?>