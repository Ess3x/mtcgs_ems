

<?php $__env->startSection('title', 'Employee Archives'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Employee Archives</h1>
                <div class="btn-group" role="group">
                    <a href="<?php echo e(route('admin.employees')); ?>" class="btn btn-primary">
                        <i class="fas fa-users"></i> Active Employees
                    </a>
                    <a href="<?php echo e(route('admin.employees-archives')); ?>" class="btn btn-danger active">
                        <i class="fas fa-archive"></i> Archives
                    </a>
                </div>
            </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <?php if(Auth::user()->admin_type === 'super_admin'): ?>
                    <div class="col-md-4">
                        <label class="form-label">Filter by Branch</label>
                        <select name="branch_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Branches</option>
                            <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($branch->id); ?>" <?php echo e($selectedBranch == $branch->id ? 'selected' : ''); ?>>
                                    <?php echo e($branch->branch_name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <div class="form-control bg-light text-dark">
                            <?php echo e(Auth::user()->profile->branch->branch_name ?? 'N/A'); ?>

                        </div>
                    </div>
                <?php endif; ?>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="<?php echo e(route('admin.employees-archives')); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Archived Employees</h6>
                    <h2 class="mb-0"><?php echo e($totalEmployees ?? 0); ?></h2>
                    <small>Inactive staff</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6>Archived Finance Officers</h6>
                    <h2 class="mb-0"><?php echo e($totalFinance ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6>Archived Administrators</h6>
                    <h2 class="mb-0"><?php echo e($totalAdmins ?? 0); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Archived Employee List</h5>
                <span class="badge bg-danger">Inactive</span>
            </div>
            <?php if(Auth::user()->isSuperAdmin()): ?>
                <small class="text-muted">Showing all archived staff</small>
            <?php else: ?>
                <small class="text-muted">Showing archived employees from your branch</small>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Employee #</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Branch</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="table-danger">
                        <td><?php echo e($emp->employee_number); ?></td>
                        <td>
                            <strong><?php echo e($emp->first_name); ?> <?php echo e($emp->last_name); ?></strong><br>
                            <small class="text-muted"><?php echo e($emp->user->email ?? 'N/A'); ?></small>
                        </td>
                        <td><?php echo e($emp->position ?? 'N/A'); ?></td>
                        <td><span class="badge bg-dark"><?php echo e($emp->branch->branch_name ?? 'N/A'); ?></span></td>
                        <td>₱<?php echo e(number_format($emp->basic_salary ?? 0, 2)); ?></td>
                        <td>
                            <span class="badge bg-danger">Inactive</span>
                        </td>
                        <td>
                            <form action="<?php echo e(route('admin.employee-restore', $emp->id)); ?>" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Restore this employee?')">
                                    <i class="fas fa-undo"></i> Restore
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="text-center py-4">No archived employees found</td>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($employees instanceof \Illuminate\Contracts\Pagination\Paginator || $employees instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator): ?>
            <div class="card-footer">
                <?php echo e($employees->links()); ?>

            </div>
        <?php endif; ?>
    </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\employees-archives.blade.php ENDPATH**/ ?>