<?php $__env->startSection('title', 'Review Approved DTR'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <a href="<?php echo e(route('finance.payroll-generation.ready-dtrs')); ?>" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left"></i> Back to Approved DTRs
            </a>
            <h2 class="mb-2">Review Approved DTR</h2>
            <p class="text-muted"><?php echo e($dtr->employeeProfile->first_name); ?> <?php echo e($dtr->employeeProfile->last_name); ?> | <?php echo e($dtr->employeeProfile->employee_number); ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-2">Period</p>
                    <h5><?php echo e($dtr->period_start->format('M d, Y')); ?> - <?php echo e($dtr->period_end->format('M d, Y')); ?></h5>
                    <span class="badge bg-success">Approved</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-2">Days Present</p>
                    <h5><?php echo e($computation['days_worked']); ?> / <?php echo e($dtr->getWorkingDays()); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-2">Net Pay Preview</p>
                    <h4>₱<?php echo e(number_format($computation['net_pay'], 2)); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Earnings</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                            <tr>
                                <th>Basic Pay</th>
                                <td>₱<?php echo e(number_format($computation['basic_pay'], 2)); ?></td>
                            </tr>
                            <tr>
                                <th>Overtime Pay</th>
                                <td>₱<?php echo e(number_format($computation['overtime_pay'], 2)); ?></td>
                            </tr>
                            <tr>
                                <th>Leave Adjustment</th>
                                <td>₱<?php echo e(number_format($computation['leave_deduction'], 2)); ?></td>
                            </tr>
                            <tr>
                                <th>Gross Pay</th>
                                <td><strong>₱<?php echo e(number_format($computation['gross_pay'], 2)); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Deductions</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                            <tr>
                                <th>Late Deduction</th>
                                <td>₱<?php echo e(number_format($computation['late_deduction'], 2)); ?></td>
                            </tr>
                            <tr>
                                <th>Absent Deduction</th>
                                <td>₱<?php echo e(number_format($computation['absent_deduction'], 2)); ?></td>
                            </tr>
                            <tr>
                                <th>SSS</th>
                                <td>₱<?php echo e(number_format($computation['sss_deduction'], 2)); ?></td>
                            </tr>
                            <tr>
                                <th>PhilHealth</th>
                                <td>₱<?php echo e(number_format($computation['philhealth_deduction'], 2)); ?></td>
                            </tr>
                            <tr>
                                <th>Pag-IBIG</th>
                                <td>₱<?php echo e(number_format($computation['pagibig_deduction'], 2)); ?></td>
                            </tr>
                            <tr>
                                <th>Withholding Tax</th>
                                <td>₱<?php echo e(number_format($computation['withholding_tax'], 2)); ?></td>
                            </tr>
                            <tr class="table-active">
                                <th>Total Deductions</th>
                                <td><strong>₱<?php echo e(number_format($computation['total_deductions'], 2)); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payroll Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="border rounded p-3 mb-3">
                                <p class="text-muted mb-1">Working Days</p>
                                <h5><?php echo e($dtr->getWorkingDays()); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 mb-3">
                                <p class="text-muted mb-1">Days Absent</p>
                                <h5><?php echo e($dtr->getDaysAbsent()); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 mb-3">
                                <p class="text-muted mb-1">Overtime Hours</p>
                                <h5><?php echo e(number_format($dtr->getTotalOvertimeHours(), 2)); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                This approved DTR has been computed for payroll preview. To generate a payroll entry, return to the approved DTR list and select the target payroll period.
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\finance\payroll-generation\review-dtr.blade.php ENDPATH**/ ?>