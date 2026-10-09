

<?php $__env->startSection('title', 'Login History'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-right-to-bracket text-primary me-2"></i>Login History</h2>
            <p class="text-muted mb-0">Review successful logins, failed attempts, and logouts.</p>
        </div>
        <span class="badge bg-dark"><?php echo e($logs->total()); ?> records</span>
    </div>

    <form method="GET" class="card shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-2">
                <label for="event" class="form-label">Event</label>
                <select id="event" name="event" class="form-select">
                    <option value="">All events</option>
                    <?php $__currentLoopData = ['login', 'logout']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($event); ?>" <?php if(request('event') === $event): echo 'selected'; endif; ?>><?php echo e(ucfirst($event)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    <?php $__currentLoopData = ['success', 'failed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($status); ?>" <?php if(request('status') === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="user_id" class="form-label">User</label>
                <select id="user_id" name="user_id" class="form-select">
                    <option value="">All users</option>
                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($user->id); ?>" <?php if((string) request('user_id') === (string) $user->id): echo 'selected'; endif; ?>><?php echo e($user->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2"><label for="from" class="form-label">From</label><input id="from" type="date" name="from" class="form-control" value="<?php echo e(request('from')); ?>"></div>
            <div class="col-md-2"><label for="to" class="form-label">To</label><input id="to" type="date" name="to" class="form-control" value="<?php echo e(request('to')); ?>"></div>
            <div class="col-md-1 d-flex gap-2"><button class="btn btn-primary" type="submit" title="Filter"><i class="fas fa-filter"></i></button><a class="btn btn-outline-secondary" href="<?php echo e(route('admin.login-history')); ?>" title="Clear"><i class="fas fa-rotate-left"></i></a></div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Date and time</th><th>User</th><th>Event</th><th>Status</th><th>Email</th><th>IP address</th><th>Device</th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-nowrap"><?php echo e($log->created_at->format('M d, Y h:i A')); ?></td>
                            <td><?php echo e($log->user?->name ?? 'Unknown user'); ?></td>
                            <td><?php echo e(ucfirst($log->event)); ?></td>
                            <td><span class="badge bg-<?php echo e($log->status === 'success' ? 'success' : 'danger'); ?>"><?php echo e(ucfirst($log->status)); ?></span></td>
                            <td><?php echo e($log->email ?: 'N/A'); ?></td>
                            <td><?php echo e($log->ip_address ?: 'N/A'); ?></td>
                            <td><small><?php echo e($log->user_agent ?: 'N/A'); ?></small></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No login history found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($logs->hasPages()): ?><div class="card-footer"><?php echo e($logs->links()); ?></div><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\login-history.blade.php ENDPATH**/ ?>