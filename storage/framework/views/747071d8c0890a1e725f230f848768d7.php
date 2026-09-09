<?php $__env->startSection('title', 'Generate Payroll from DTRs'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">Payroll Generation from DTRs</h2>
            <p class="text-muted mt-2">Select approved DTRs to generate payroll entries</p>
        </div>
    </div>

    <!-- Approved DTRs Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Approved DTRs Ready for Payroll Generation</h5>
                </div>
                <div class="card-body">
                    <?php if($approvedDTRs->count() > 0): ?>
                        <form method="POST" id="bulkGenerateForm">
                            <?php echo csrf_field(); ?>
                            
                            <!-- Select Payroll Period -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="payroll_period_id" class="form-label">Select Payroll Period:</label>
                                    <select class="form-select" id="payroll_period_id" name="payroll_period_id" required>
                                        <option value="">-- Select Period --</option>
                                        <?php $__currentLoopData = $payrollPeriods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($period->id); ?>">
                                                <?php echo e($period->period_code); ?> (<?php echo e($period->start_date->format('M d')); ?> - <?php echo e($period->end_date->format('M d, Y')); ?>)
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="" class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">
                                        <i class="fas fa-cogs"></i> Generate All for Selected Period
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Employee ID</th>
                                        <th>Period</th>
                                        <th class="text-center">Days Present</th>
                                        <th class="text-center">Total Hours</th>
                                        <th class="text-center">Overtime</th>
                                        <th class="text-center">Basic Pay</th>
                                        <th class="text-center">Absent Deduction</th>
                                        <th class="text-center">Net Pay</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $approvedDTRs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dtr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php $calculation = $dtrCalculations[$dtr->id] ?? null; ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($dtr->employeeProfile->first_name); ?> <?php echo e($dtr->employeeProfile->last_name); ?></strong>
                                            </td>
                                            <td><?php echo e($dtr->employeeProfile->employee_number); ?></td>
                                            <td><?php echo e($dtr->period_start->format('M d')); ?> - <?php echo e($dtr->period_end->format('M d, Y')); ?></td>
                                            <td class="text-center"><?php echo e($dtr->getDaysPresent()); ?>/<?php echo e($dtr->getWorkingDays()); ?></td>
                                            <td class="text-center"><?php echo e(number_format($dtr->getTotalHoursWorked(), 1)); ?> hrs</td>
                                            <td class="text-center">
                                                <?php $ot = $dtr->getTotalOvertimeHours(); ?>
                                                <?php if($ot > 0): ?>
                                                    <span class="badge bg-warning"><?php echo e(number_format($ot, 1)); ?> hrs</span>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($calculation): ?>
                                                    ₱<?php echo e(number_format($calculation['basic_pay'], 2)); ?>

                                                <?php else: ?>
                                                    --
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($calculation): ?>
                                                    ₱<?php echo e(number_format($calculation['absent_deduction'], 2)); ?>

                                                <?php else: ?>
                                                    --
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($calculation): ?>
                                                    ₱<?php echo e(number_format($calculation['net_pay'], 2)); ?>

                                                <?php else: ?>
                                                    --
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="<?php echo e(route('finance.payroll-generation.review-dtr', $dtr->id)); ?>" class="btn btn-sm btn-outline-primary" title="Review Computation">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form action="<?php echo e(route('finance.payroll-generation.from-dtr', $dtr->id)); ?>" method="POST" style="display: inline;">
                                                        <?php echo csrf_field(); ?>
                                                        <select class="form-select form-select-sm d-inline-block" name="payroll_period_id" style="width: 150px;" onchange="this.form.submit()">
                                                            <option value="">Select Period</option>
                                                            <?php $__currentLoopData = $payrollPeriods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($period->id); ?>">
                                                                    <?php echo e($period->period_code); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3">
                            <?php echo e($approvedDTRs->links()); ?>

                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No approved DTRs ready for payroll generation.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Generate Modal -->
<div class="modal fade" id="bulkGenerateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Payroll for Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="bulkGenerateFormModal">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <p class="text-muted">This will generate payroll entries for all approved DTRs in the selected period.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Make sure the payroll period is correct before proceeding.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Payroll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('payroll_period_id').addEventListener('change', function() {
        const periodId = this.value;
        const form = document.getElementById('bulkGenerateFormModal');
        form.action = `/finance/payroll-generation/period/${periodId}/generate`;
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\finance\payroll-generation\ready-dtrs.blade.php ENDPATH**/ ?>