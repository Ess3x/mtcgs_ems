<?php $__env->startSection('title', 'Daily Time Record (DTR)'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Daily Time Record (DTR)</h2>
                    <p class="text-muted mt-2"><?php echo e($employeeProfile->first_name); ?> <?php echo e($employeeProfile->last_name); ?></p>
                </div>
                <div>
                    <a href="<?php echo e(route('employee.dtr.summary')); ?>" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Summary
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Period Card -->
    <?php if($currentDTR): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Current Period</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Period</p>
                            <h6><?php echo e($currentDTR->period_start->format('M d')); ?> - <?php echo e($currentDTR->period_end->format('M d, Y')); ?></h6>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Status</p>
                            <span class="badge bg-<?php echo e($currentDTR->status === 'draft' ? 'warning' : ($currentDTR->status === 'submitted' ? 'info' : ($currentDTR->status === 'approved' ? 'success' : 'danger'))); ?>">
                                <?php echo e(ucfirst($currentDTR->status)); ?>

                            </span>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Days Present</p>
                            <h6><?php echo e($currentDTR->getDaysPresent()); ?> / <?php echo e($currentDTR->getWorkingDays()); ?></h6>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="<?php echo e(route('employee.dtr.show', $currentDTR->id)); ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-info mb-4">
            No DTR has been generated for the current period yet.
        </div>
    <?php endif; ?>

    <!-- DTR History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">DTR History</h5>
                </div>
                <div class="card-body">
                    <?php if($dtrs->count() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th class="text-center">Days Present</th>
                                        <th class="text-center">Hours Worked</th>
                                        <th class="text-center">Overtime</th>
                                        <th class="text-center">Late (mins)</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $dtrs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dtr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($dtr->period_start->format('M d')); ?> - <?php echo e($dtr->period_end->format('M d, Y')); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <?php echo e($dtr->getDaysPresent()); ?>/<?php echo e($dtr->getWorkingDays()); ?>

                                            </td>
                                            <td class="text-center">
                                                <?php echo e(number_format($dtr->getTotalHoursWorked(), 1)); ?> hrs
                                            </td>
                                            <td class="text-center">
                                                <?php $overtime = $dtr->getTotalOvertimeHours(); ?>
                                                <?php if($overtime > 0): ?>
                                                    <span class="badge bg-warning"><?php echo e(number_format($overtime, 1)); ?> hrs</span>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php $late = $dtr->getTotalLateMinutes(); ?>
                                                <?php if($late > 0): ?>
                                                    <span class="badge bg-danger"><?php echo e($late); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo e($dtr->status === 'draft' ? 'warning' : ($dtr->status === 'submitted' ? 'info' : ($dtr->status === 'approved' ? 'success' : 'danger'))); ?>">
                                                    <?php echo e(ucfirst($dtr->status)); ?>

                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?php echo e(route('employee.dtr.show', $dtr->id)); ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3">
                            <?php echo e($dtrs->links()); ?>

                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No DTR records found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\employee\dtr\index.blade.php ENDPATH**/ ?>