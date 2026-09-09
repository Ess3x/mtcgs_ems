<?php $__env->startSection('title', 'ID Verification'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h2><i class="fas fa-id-card me-2"></i> ID Verification</h2>
                    <p class="mb-0">Review and verify employee registrations</p>
                </div>
            </div>
        </div>
    </div>

    <!-- SIMPLE BUTTON TABS - GARANTISADONG HIWALAY -->
    <div class="mb-3">
        <a href="?tab=pending" class="btn <?php echo e(request('tab') == 'rejected' || request('tab') == 'deactivated' ? 'btn-secondary' : 'btn-primary'); ?>">
            <i class="fas fa-clock"></i> Pending (<?php echo e($pendingUsers->count()); ?>)
        </a>
        <a href="?tab=rejected" class="btn <?php echo e(request('tab') == 'rejected' ? 'btn-primary' : 'btn-secondary'); ?>">
            <i class="fas fa-times-circle"></i> Rejected (<?php echo e($rejectedUsers->count()); ?>)
        </a>
        <a href="?tab=deactivated" class="btn <?php echo e(request('tab') == 'deactivated' ? 'btn-primary' : 'btn-secondary'); ?>">
            <i class="fas fa-ban"></i> Deactivated (<?php echo e($deactivatedUsers->count()); ?>)
        </a>
        <?php if(Auth::user()->admin_type === 'super_admin'): ?>
            <a href="?tab=status_changes" class="btn <?php echo e(request('tab') == 'status_changes' ? 'btn-primary' : 'btn-secondary'); ?>">
                <i class="fas fa-user-edit"></i> Status Changes (<?php echo e($pendingStatusChanges->count()); ?>)
            </a>
        <?php endif; ?>
    </div>

    <?php if(request('tab') == 'status_changes' && Auth::user()->admin_type === 'super_admin'): ?>
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-user-edit text-warning me-2"></i>Pending Employee Status Changes</h5>
            <small class="text-muted">Review changes submitted by branch heads before applying them.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr><th>Date</th><th>Employee</th><th>Email</th><th>Branch</th><th>Current Status</th><th>Requested Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $pendingStatusChanges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e(optional($employee->status_change_requested_at)->format('Y-m-d H:i')); ?></td>
                        <td><strong><?php echo e($employee->first_name); ?> <?php echo e($employee->last_name); ?></strong></td>
                        <td><?php echo e($employee->user->email ?? 'N/A'); ?></td>
                        <td><?php echo e($employee->branch->branch_name ?? 'N/A'); ?></td>
                        <td><span class="badge bg-secondary"><?php echo e($employee->status ?? 'New Hire'); ?></span></td>
                        <td><span class="badge bg-warning text-dark"><?php echo e($employee->pending_status); ?></span></td>
                        <td class="text-nowrap">
                            <form action="<?php echo e(route('admin.verify.status-change.approve', $employee->id)); ?>" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button class="btn btn-sm btn-success" onclick="return confirm('Approve this status change?')">Approve</button>
                            </form>
                            <form action="<?php echo e(route('admin.verify.status-change.reject', $employee->id)); ?>" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="reason" value="Rejected by System Administrator">
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Reject this status change?')">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="text-center py-5">No pending status changes</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- PENDING TABLE (visible pag walang tab o pending ang napili) -->
    <?php if(!request('tab') || request('tab') == 'pending'): ?>
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-clock text-warning me-2"></i>Pending Verifications</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>Name</th><th>Email</th><th>Employee #</th><th>Role</th><th>ID</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $pendingUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($user->created_at->format('Y-m-d H:i')); ?></small></td>
                        <td><strong><?php echo e($user->name); ?></strong></td>
                        <td><?php echo e($user->email); ?></td>
                        <td><?php echo e(optional($user->profile)->employee_number ?? 'N/A'); ?></td>
                        <td><span class="badge bg-secondary"><?php echo e(ucfirst($user->role)); ?></span></td>
                        <td>
                            <?php if($user->id_document_path): ?>
                                <a href="<?php echo e(route('admin.verify.document', $user->id)); ?>" target="_blank" class="btn btn-sm btn-info me-1">View ID</a>
                            <?php else: ?>
                                <span class="text-muted">No ID</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo e($user->id); ?>">View</button>
                            <form action="<?php echo e(route('admin.verify.approve', $user->id)); ?>" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button class="btn btn-sm btn-success me-1" onclick="return confirm('Approve?')">Approve</button>
                            </form>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo e($user->id); ?>">Reject</button>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="text-center py-5">No pending verifications</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- REJECTED TABLE (visible pag rejected ang napili) -->
    <?php if(request('tab') == 'rejected'): ?>
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-times-circle text-danger me-2"></i>Rejected Registrations</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>Name</th><th>Email</th><th>Employee #</th><th>Role</th><th>Reason</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $rejectedUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($user->updated_at->format('Y-m-d H:i')); ?></small></td>
                        <td><strong><?php echo e($user->name); ?></strong></td>
                        <td><?php echo e($user->email); ?></td>
                        <td><?php echo e(optional($user->profile)->employee_number ?? 'N/A'); ?></td>
                        <td><span class="badge bg-danger"><?php echo e(ucfirst($user->role)); ?></span></td>
                        <td><?php echo e($user->rejection_reason ?? 'No reason'); ?></small></td>
                        <td>
                            <form action="<?php echo e(route('admin.verify.approve', $user->id)); ?>" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button class="btn btn-sm btn-success" onclick="return confirm('Approve?')">Approve</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="text-center py-5">No rejected registrations</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- DEACTIVATED TABLE (visible pag deactivated ang napili) -->
    <?php if(request('tab') == 'deactivated'): ?>
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-ban text-secondary me-2"></i>Deactivated Accounts</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th><th>Email</th><th>Role</th><th>Deactivated Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $deactivatedUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><strong><?php echo e($user->name); ?></strong></td>
                        <td><?php echo e($user->email); ?></td>
                        <td><span class="badge bg-secondary"><?php echo e(ucfirst($user->role)); ?></span></td>
                        <td><?php echo e($user->updated_at->format('Y-m-d H:i')); ?></small></td>
                        <td>
                            <form action="<?php echo e(route('admin.verify.reactivate', $user->id)); ?>" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button class="btn btn-sm btn-success" onclick="return confirm('Reactivate?')">Reactivate</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <td><td colspan="5" class="text-center py-5">No deactivated accounts</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Reject Modals -->
<?php $__currentLoopData = $pendingUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="modal fade" id="viewModal<?php echo e($user->id); ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verification details: <?php echo e($user->name); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row gx-4 gy-3 align-items-start">
                    <div class="col-lg-6">
                        <div class="card border-secondary mb-3 h-100">
                            <div class="card-header bg-light"><strong>User Details</strong></div>
                            <div class="card-body p-3">
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <th style="width:30%;" class="text-end pe-3">Name</th>
                                            <td><?php echo e($user->name); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Email</th>
                                            <td><?php echo e($user->email); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Employee #</th>
                                            <td><?php echo e(optional($user->profile)->employee_number ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Role</th>
                                            <td><?php echo e(ucfirst($user->role)); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Position</th>
                                            <td><?php echo e(optional($user->profile)->position ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Branch</th>
                                            <td><?php echo e(optional(optional($user->profile)->branch)->branch_name ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Submitted at</th>
                                            <td><?php echo e($user->created_at->format('Y-m-d H:i')); ?></td>
                                        </tr>
                                        <?php if(optional($user->profile)->contact_number): ?>
                                        <tr>
                                            <th class="text-end pe-3">Contact</th>
                                            <td><?php echo e($user->profile->contact_number); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if(optional($user->profile)->address): ?>
                                        <tr>
                                            <th class="text-end pe-3 align-top">Address</th>
                                            <td><?php echo e($user->profile->address); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-secondary mb-3">
                            <div class="card-header bg-light"><strong>ID Document</strong></div>
                            <div class="card-body text-center">
                                <?php if($user->id_document_path): ?>
                                    <?php
                                        $documentUrl = route('admin.verify.document', $user->id);
                                        $extension = strtolower(pathinfo($user->id_document_path, PATHINFO_EXTENSION));
                                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                                    ?>
                                    <?php if(in_array($extension, $imageExtensions)): ?>
                                        <img src="<?php echo e($documentUrl); ?>" alt="ID Document" class="img-fluid rounded" style="max-height:420px; width:auto;" />
                                    <?php else: ?>
                                        <p class="text-muted">ID file is not a supported image preview.</p>
                                        <a href="<?php echo e($documentUrl); ?>" target="_blank" class="btn btn-sm btn-info">Open ID Document</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted">No ID document uploaded.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form action="<?php echo e(route('admin.verify.approve', $user->id)); ?>" method="POST" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal<?php echo e($user->id); ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo e(route('admin.verify.reject', $user->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Reject: <?php echo e($user->name); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Reason for rejection:</label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Enter reason..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\verifications.blade.php ENDPATH**/ ?>