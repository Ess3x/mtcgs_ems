<?php $__env->startSection('content'); ?>
<style>
    .leave-action-btn {
        width: 150px;
        box-sizing: border-box;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        white-space: nowrap;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Leave Requests</h1>
    <a href="<?php echo e(route('leave.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> File Leave
    </a>
</div>

<?php if(in_array(Auth::user()->role, ['employee', 'finance_officer', 'finance_head'], true)): ?>
    <?php
        $hasPendingLeave = ($leaves ?? collect())->contains(function ($leave) {
            return in_array($leave->status, ['pending', 'pending_system_admin'], true);
        });
    ?>
    <?php if($hasPendingLeave): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Leave request submitted.</strong> Your leave is waiting for Branch Head and System Administrator approval. Leave credits will be deducted only after final approval by the System Administrator.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $leaves ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e(optional($leave->employeeProfile)->employee_number ?? 'N/A'); ?></td>
                        <td><?php echo e(optional($leave->employeeProfile)->full_name ?? 'N/A'); ?></td>
                        <td><?php echo e(optional($leave->employeeProfile)->position ?? 'N/A'); ?></td>
                        <td><?php echo e(ucfirst($leave->leave_type)); ?></td>
                        <td><?php echo e($leave->start_date); ?> to <?php echo e($leave->end_date); ?></td>
                        <td><?php echo e($leave->total_days); ?></td>
                        <td><?php echo e($leave->reason); ?></td>
                        <td>
                            <?php if($leave->status == 'approved'): ?>
                                <?php if($leave->is_absent): ?>
                                    <span class="badge bg-danger">Approved - Absent</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php endif; ?>
                            <?php elseif($leave->status == 'pending_system_admin'): ?>
                                <span class="badge bg-info text-dark">Pending System Administrator Approval</span>
                            <?php elseif($leave->status == 'pending'): ?>
                                <?php if(optional($leave->employeeProfile)->user?->admin_type === 'branch_admin'): ?>
                                    <span class="badge bg-warning text-dark">Pending System Administrator Approval</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending Branch Head Approval</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if(Auth::user()->isAdmin()): ?>
                                <?php if($leave->status == 'pending' && Auth::user()->isBranchAdmin() && !Auth::user()->isSuperAdmin() && optional($leave->employeeProfile)->user?->admin_type !== 'branch_admin'): ?>
                                    <a href="<?php echo e(route('leave.approve', $leave->id)); ?>" class="btn btn-sm btn-success leave-action-btn" title="Approve leave request">
                                        <i class="fas fa-check"></i> Approve & Forward
                                    </a>
                                    <a href="<?php echo e(route('leave.reject', $leave->id)); ?>" class="btn btn-sm btn-danger leave-action-btn" title="Reject leave request">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                <?php elseif(Auth::user()->admin_type === 'super_admin' && in_array($leave->status, ['pending', 'pending_system_admin'], true)): ?>
                                    <a href="<?php echo e(route('leave.approve', $leave->id)); ?>" class="btn btn-sm btn-success leave-action-btn" title="Approve leave request">
                                        <i class="fas fa-check"></i> Final Approve
                                    </a>
                                    <a href="<?php echo e(route('leave.reject', $leave->id)); ?>" class="btn btn-sm btn-danger leave-action-btn" title="Reject leave request">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            <?php elseif(in_array(Auth::user()->role, ['employee', 'finance_officer', 'finance_head']) && $leave->status == 'rejected'): ?>
                                <a href="<?php echo e(route('leave.create')); ?>" class="btn btn-sm btn-outline-primary" title="File another leave request">
                                    <i class="fas fa-redo"></i> File Again
                                </a>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox"></i> No leave requests
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views/leave/index.blade.php ENDPATH**/ ?>