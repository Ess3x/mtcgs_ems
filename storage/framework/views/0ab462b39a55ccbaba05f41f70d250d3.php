

<?php $__env->startSection('title', 'Audit Trail'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .audit-trail-table td,
    .audit-trail-table th {
        white-space: nowrap;
    }
    .audit-trail-table details {
        min-width: 180px;
        white-space: normal;
    }
    .audit-trail-table details summary {
        cursor: pointer;
        color: #2563eb;
        font-weight: 600;
    }
    .audit-trail-table details small {
        overflow-wrap: anywhere;
    }
    .audit-pagination {
        overflow-x: auto;
    }
    .audit-pagination .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
    }
    body.dark-mode .audit-trail-table tbody tr,
    body.dark-mode .audit-trail-table tbody td {
        background-color: #1e293b;
        color: #e2e8f0;
        border-color: #475569;
    }
    body.dark-mode .audit-trail-table tbody tr:hover,
    body.dark-mode .audit-trail-table tbody tr:hover td {
        background-color: #334155;
    }
    body.dark-mode .audit-trail-table details summary {
        color: #93c5fd;
    }
    body.dark-mode .audit-pagination .page-link {
        background-color: #1e293b;
        border-color: #475569;
        color: #e2e8f0;
    }
    body.dark-mode .audit-pagination .page-item.active .page-link {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    body.dark-mode .audit-pagination .page-item.disabled .page-link {
        background-color: #0f172a;
        color: #64748b;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-shield-alt text-primary me-2"></i>Audit Trail</h2>
            <p class="text-muted mb-0">Track who changed system records, when the change happened, and what changed.</p>
        </div>
        <span class="badge bg-dark"><?php echo e($logs->total()); ?> recorded actions</span>
    </div>

    <form method="GET" class="card shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-2">
                <label for="action" class="form-label">Action</label>
                <select id="action" name="action" class="form-select">
                    <option value="">All actions</option>
                    <?php $__currentLoopData = ['created', 'updated', 'deleted']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($action); ?>" <?php if(request('action') === $action): echo 'selected'; endif; ?>><?php echo e(ucfirst($action)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="user_id" class="form-label">Performed by</label>
                <select id="user_id" name="user_id" class="form-select">
                    <option value="">All users</option>
                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($user->id); ?>" <?php if((string) request('user_id') === (string) $user->id): echo 'selected'; endif; ?>><?php echo e($user->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="from" class="form-label">From</label>
                <input id="from" type="date" name="from" class="form-control" value="<?php echo e(request('from')); ?>">
            </div>
            <div class="col-md-2">
                <label for="to" class="form-label">To</label>
                <input id="to" type="date" name="to" class="form-control" value="<?php echo e(request('to')); ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Filter</button>
                <a class="btn btn-outline-secondary" href="<?php echo e(route('admin.audit-logs')); ?>">Clear</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 audit-trail-table">
                <thead class="table-light">
                    <tr>
                        <th>Date and time</th>
                        <th>Performed by</th>
                        <th>Action</th>
                        <th>Record</th>
                        <th>Changes</th>
                        <th>MAC / Device Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-nowrap"><?php echo e($log->created_at->format('M d, Y h:i A')); ?></td>
                            <td><?php echo e($log->user?->name ?? 'System'); ?></td>
                            <td><span class="badge bg-<?php echo e($log->action === 'deleted' ? 'danger' : ($log->action === 'created' ? 'success' : 'warning text-dark')); ?>"><?php echo e(ucfirst($log->action)); ?></span></td>
                            <td><?php echo e(class_basename($log->auditable_type)); ?> #<?php echo e($log->auditable_id ?? 'new'); ?></td>
                            <td>
                                <?php ($changes = array_keys($log->new_values ?? $log->old_values ?? [])); ?>
                                <details>
                                    <summary><?php echo e(count($changes)); ?> field(s)</summary>
                                    <small class="d-block mt-2"><?php echo e(implode(', ', $changes) ?: 'No field details'); ?></small>
                                    <?php if($log->old_values): ?>
                                        <small class="d-block text-muted mt-1">Before: <?php echo e(json_encode($log->old_values)); ?></small>
                                    <?php endif; ?>
                                    <?php if($log->new_values): ?>
                                        <small class="d-block text-muted">After: <?php echo e(json_encode($log->new_values)); ?></small>
                                    <?php endif; ?>
                                </details>
                            </td>
                            <td><?php echo e($log->ip_address ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No audit records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($logs->hasPages()): ?>
            <div class="card-footer audit-pagination"><?php echo e($logs->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\audit-logs.blade.php ENDPATH**/ ?>