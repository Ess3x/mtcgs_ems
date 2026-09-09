<?php $__env->startSection('title', 'Branches Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Branches Management</h4>
                    <?php if(Auth::user()->isSuperAdmin()): ?>
                        <a href="<?php echo e(route('admin.branches.create')); ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Branch
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Branch Code</th>
                                    <th>Branch Name</th>
                                    <th>Address</th>
                                    <th>Employees Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($branch->branch_code); ?></td>
                                        <td><?php echo e($branch->branch_name); ?></td>
                                        <td><?php echo e($branch->address); ?></td>
                                        <td><?php echo e($branch->employees()->count()); ?></td>
                                        <td>
                                            <a href="<?php echo e(route('admin.branches.show', $branch)); ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if(Auth::user()->isSuperAdmin()): ?>
                                                <a href="<?php echo e(route('admin.branches.edit', $branch)); ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <form action="<?php echo e(route('admin.branches.destroy', $branch)); ?>" method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this branch?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No branches found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\branches\index.blade.php ENDPATH**/ ?>