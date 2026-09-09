<?php $__env->startSection('title', 'User Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <h1 class="h3 mb-4">User Management</h1>

    <?php if(Auth::user()->admin_type === 'super_admin' && $pendingFinanceChanges->count()): ?>
        <div class="card border-warning mb-4">
            <div class="card-header bg-warning-subtle">
                <h5 class="mb-0"><i class="fas fa-user-clock me-2"></i>Pending Finance Officer Changes</h5>
                <small>Review edits submitted by Branch Heads before applying them.</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Finance Officer</th><th>Branch</th><th>Current Details</th><th>Requested Details</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php $__currentLoopData = $pendingFinanceChanges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $finance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($finance->first_name); ?> <?php echo e($finance->last_name); ?><br><small><?php echo e($finance->user->email ?? 'N/A'); ?></small></td>
                            <td><?php echo e($finance->branch->branch_name ?? 'N/A'); ?></td>
                            <td><?php echo e($finance->position); ?><br><?php echo e($finance->user->email ?? 'N/A'); ?></td>
                            <td><?php echo e($finance->pending_changes['first_name'] ?? ''); ?> <?php echo e($finance->pending_changes['last_name'] ?? ''); ?><br><?php echo e($finance->pending_changes['position'] ?? ''); ?><br><?php echo e($finance->pending_changes['email'] ?? ''); ?></td>
                            <td class="text-nowrap">
                                <form method="POST" action="<?php echo e(route('admin.user-finance-approve', $finance->id)); ?>" class="d-inline"><?php echo csrf_field(); ?><button class="btn btn-sm btn-success" onclick="return confirm('Approve these Finance Officer changes?')"><i class="fas fa-check"></i> Approve</button></form>
                                <form method="POST" action="<?php echo e(route('admin.user-finance-reject', $finance->id)); ?>" class="d-inline"><?php echo csrf_field(); ?><button class="btn btn-sm btn-danger" onclick="return confirm('Reject these Finance Officer changes?')"><i class="fas fa-times"></i> Reject</button></form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body"><h5>Total Users</h5><h2><?php echo e($totalUsers ?? 0); ?></h2></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body"><h5>Active</h5><h2><?php echo e($activeCount ?? 0); ?></h2></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body"><h5>Inactive</h5><h2><?php echo e($inactiveCount ?? 0); ?></h2></div>
            </div>
        </div>
    </div>
    
    <ul class="nav nav-tabs tab-switcher mb-3">
        <li class="nav-item"><button class="nav-link <?php echo e(request('tab') === 'finance' ? '' : 'active'); ?>" data-bs-toggle="tab" data-bs-target="#empTab">Employees (<?php echo e(count($employees)); ?>)</button></li>
        <li class="nav-item"><button class="nav-link <?php echo e(request('tab') === 'finance' ? 'active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#finTab">Finance Officers (<?php echo e(count($financeOfficers)); ?>)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#admTab">Administrators (<?php echo e(count($admins)); ?>)</button></li>
    </ul>

    <style>
        .tab-switcher {
            display: inline-flex;
            width: auto;
            background: rgba(9, 30, 45, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 10px 10px 0 0;
            overflow: hidden;
            margin-bottom: 0;
        }

        .tab-switcher .nav-item {
            margin: 0;
        }

        .tab-switcher .nav-link {
            border: 0;
            border-radius: 0;
            color: #a9d2ff;
            background: transparent;
            padding: 0.9rem 1.25rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .tab-switcher .nav-link:hover {
            color: #ffffff;
        }

        .tab-switcher .nav-link.active {
            background: #f5f7fb;
            color: #0f172a;
            box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.08);
        }
    </style>
    
    <div class="tab-content mt-3">
        <div class="tab-pane fade <?php echo e(request('tab') === 'finance' ? '' : 'show active'); ?>" id="empTab">
            <div class="card"><div class="card-header">Employee List</div>
            <div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Position</th><th>Branch</th><th>Status</th><th>Action</th></tr></thead>
            <tbody><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($emp['name']); ?></td><td><?php echo e($emp['email']); ?></td><td><?php echo e($emp['position'] ?? 'N/A'); ?></td><td><?php echo e($emp['branch'] ?? 'N/A'); ?></td><td><?php if($emp['is_active']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-danger">Inactive</span><?php endif; ?></td><td><a href="<?php echo e(route('admin.user-edit', ['role' => 'employee', 'id' => $emp['profile_id']])); ?>" class="btn btn-sm btn-primary">Edit</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody></table></div></div>
        </div>
        <div class="tab-pane fade <?php echo e(request('tab') === 'finance' ? 'show active' : ''); ?>" id="finTab">
            <div class="card"><div class="card-header">Finance Officer List</div>
            <div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Position</th><th>Status</th><th>Action</th></tr></thead>
            <tbody><?php $__currentLoopData = $financeOfficers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($fin['name']); ?></td><td><?php echo e($fin['email']); ?></td><td><?php echo e($fin['position'] ?? 'Finance Officer'); ?></td><td><?php if($fin['is_active']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-danger">Inactive</span><?php endif; ?></td><td><a href="<?php echo e(route('admin.user-edit', ['role' => 'finance', 'id' => $fin['profile_id']])); ?>" class="btn btn-sm btn-primary">Edit</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody></table></div></div>
        </div>
        <div class="tab-pane fade" id="admTab">
            <div class="card"><div class="card-header">Administrator List</div>
            <div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Position</th><th>Status</th><th>Action</th></tr></thead>
            <tbody><?php $__currentLoopData = $admins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($admin['name']); ?></td><td><?php echo e($admin['email']); ?></td><td><?php echo e($admin['position'] ?? 'Administrator'); ?></td><td><?php if($admin['is_active']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-danger">Inactive</span><?php endif; ?></td><td><a href="<?php echo e(route('admin.user-edit', ['role' => 'admin', 'id' => $admin['profile_id']])); ?>" class="btn btn-sm btn-primary">Edit</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody></table></div></div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\user-management.blade.php ENDPATH**/ ?>