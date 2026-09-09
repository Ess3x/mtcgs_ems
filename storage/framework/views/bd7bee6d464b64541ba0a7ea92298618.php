<?php $__env->startSection('title', 'My Payslips'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <h1 class="h3 mb-4">My Payslips</h1>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Payroll History</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Pay Period</th>
                        <th>Basic Pay</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $payslips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payslip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($payslip->payrollPeriod->period_code); ?></small></td>
                        <td><?php echo e(date('M d, Y', strtotime($payslip->payrollPeriod->start_date))); ?><br>
                            <small>to <?php echo e(date('M d, Y', strtotime($payslip->payrollPeriod->end_date))); ?></small>
                        </td>
                        <td>₱<?php echo e(number_format($payslip->basic_pay, 2)); ?></small></td>
                        <td><strong>₱<?php echo e(number_format($payslip->gross_pay, 2)); ?></strong></small></td>
                        <td>₱<?php echo e(number_format($payslip->total_deductions, 2)); ?></small></td>
                        <td><strong class="text-success">₱<?php echo e(number_format($payslip->net_pay, 2)); ?></strong></small></td>
                        <td>
                            <a href="<?php echo e(route('admin.payroll.payslip', $payslip->id)); ?>" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="<?php echo e(route('admin.payroll.download-payslip', $payslip->id)); ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-download"></i> PDF
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <td><td colspan="7" class="text-center">No payslips found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <?php echo e($payslips->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views/employee/payslips.blade.php ENDPATH**/ ?>