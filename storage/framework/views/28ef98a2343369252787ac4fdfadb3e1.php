<?php $__env->startSection('title', 'Payroll Summary'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-2">Payroll Summary - <?php echo e($payrollPeriod->period_code); ?></h2>
            <p class="text-muted"><?php echo e($payrollPeriod->start_date->format('F d, Y')); ?> - <?php echo e($payrollPeriod->end_date->format('F d, Y')); ?></p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Employees</p>
                    <h3 class="mb-0"><?php echo e($stats['total_employees']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Draft</p>
                    <h3 class="mb-0 text-warning"><?php echo e($stats['draft_count']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Processed</p>
                    <h3 class="mb-0 text-info"><?php echo e($stats['processed_count']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Approved</p>
                    <h3 class="mb-0 text-success"><?php echo e($stats['approved_count']); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <p class="mb-1 small">Total Gross Pay</p>
                    <h4 class="mb-0">₱ <?php echo e(number_format($stats['total_gross'], 2)); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <p class="mb-1 small">Total Deductions</p>
                    <h4 class="mb-0">₱ <?php echo e(number_format($stats['total_deductions'], 2)); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <p class="mb-1 small">Total Net Pay</p>
                    <h4 class="mb-0">₱ <?php echo e(number_format($stats['total_net'], 2)); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Deduction Breakdown -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">SSS Contributions</h6>
                </div>
                <div class="card-body text-center">
                    <h4 class="mb-0 text-primary">₱ <?php echo e(number_format($stats['total_sss'], 2)); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">PhilHealth</h6>
                </div>
                <div class="card-body text-center">
                    <h4 class="mb-0 text-primary">₱ <?php echo e(number_format($stats['total_philhealth'], 2)); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Pag-IBIG</h6>
                </div>
                <div class="card-body text-center">
                    <h4 class="mb-0 text-primary">₱ <?php echo e(number_format($stats['total_pagibig'], 2)); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Withholding Tax</h6>
                </div>
                <div class="card-body text-center">
                    <h4 class="mb-0 text-primary">₱ <?php echo e(number_format($stats['total_tax'], 2)); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Entries Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payroll Entries Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee Name</th>
                                    <th class="text-center">Gross Pay</th>
                                    <th class="text-center">Deductions</th>
                                    <th class="text-center">Net Pay</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $payrollEntries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($entry->employeeProfile->first_name); ?> <?php echo e($entry->employeeProfile->last_name); ?></strong><br>
                                            <small class="text-muted"><?php echo e($entry->employeeProfile->employee_number); ?></small>
                                        </td>
                                        <td class="text-center">₱ <?php echo e(number_format($entry->gross_pay, 2)); ?></td>
                                        <td class="text-center">₱ <?php echo e(number_format($entry->total_deductions, 2)); ?></td>
                                        <td class="text-center"><strong>₱ <?php echo e(number_format($entry->net_pay, 2)); ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo e($entry->status === 'draft' ? 'warning' : ($entry->status === 'processed' ? 'info' : 'success')); ?>">
                                                <?php echo e(ucfirst($entry->status)); ?>

                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo e(route('finance.payroll-generation.entry', $entry->id)); ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Review
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No payroll entries for this period</td>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\finance\payroll-generation\summary.blade.php ENDPATH**/ ?>