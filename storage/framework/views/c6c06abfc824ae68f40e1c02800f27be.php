<?php $__env->startSection('title', 'Payroll Entries'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .payroll-entries-table th:last-child,
    .payroll-entries-table td:last-child {
        min-width: 190px;
    }

    .payroll-entry-actions {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
        min-width: 170px;
    }

    .payroll-entry-actions > a,
    .payroll-entry-actions > form,
    .payroll-entry-actions > form > button {
        width: 100%;
    }

    .payroll-entry-actions > form {
        display: block;
        margin: 0;
    }

    .payroll-entry-actions small {
        display: block;
        max-width: 170px;
        overflow-wrap: anywhere;
        line-height: 1.3;
    }
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Payroll Entries - <?php echo e($period->period_code); ?></h1>
        <div>
            <a href="<?php echo e(route('admin.payroll.periods')); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Total Employees</h6>
                    <h3><?php echo e($summary['total_employees']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Gross Pay</h6>
                    <h3>₱<?php echo e(number_format($summary['total_gross'], 2)); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Total Deductions</h6>
                    <h3>₱<?php echo e(number_format($summary['total_deductions'], 2)); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Net Pay</h6>
                    <h3>₱<?php echo e(number_format($summary['total_net'], 2)); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Employee Payroll Entries</h5>
        </div>
        <?php
            $correctionStage = $period->correction_stage;
            $isFinanceOfficer = auth()->user()->role === 'finance_officer';
            $isFinanceHead = auth()->user()->role === 'finance_head';
            $isBranchHeadReviewer = auth()->user()->isBranchAdmin();
            $isHrReviewer = auth()->user()->isSuperAdmin();
            $selectedAction = $isFinanceOfficer && !$correctionStage
                ? route('admin.payroll.return-selected-to-bh', $period->id)
                : ($isBranchHeadReviewer && $correctionStage === 'bh_review'
                    ? route('admin.payroll.return-selected-to-hr', $period->id)
                    : ($isHrReviewer && $correctionStage === 'hr_review'
                        ? route('admin.payroll.return-selected-to-fh', $period->id)
                        : route('admin.payroll.return-selected-to-bh', $period->id)));
        ?>
        <?php if($correctionStage): ?>
            <div class="alert alert-info m-3 mb-0">
                <strong>Correction stage:</strong> <?php echo e(str_replace('_', ' ', ucfirst($correctionStage))); ?>

                <?php if($period->correction_reason): ?>
                    <br><span class="small"><strong>Reason:</strong> <?php echo e($period->correction_reason); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if($entries->isNotEmpty()): ?>
            <div class="card-body border-bottom">
                <form method="POST" id="selected-correction-form" action="<?php echo e($selectedAction); ?>" class="row g-2 align-items-end">
                    <?php echo csrf_field(); ?>
                    <div id="selected-entry-inputs"></div>
                    <div class="col-md-5">
                        <label for="correction-reason" class="form-label mb-1">Reason / correction instruction</label>
                        <input id="correction-reason" name="reason" class="form-control" required maxlength="2000" placeholder="Example: Recheck late deduction and net pay">
                    </div>
                    <div class="col-md-7 d-flex flex-wrap gap-2">
                        <?php if($isFinanceOfficer && !$correctionStage): ?>
                            <button type="submit" class="btn btn-outline-warning" data-correction-action="<?php echo e(route('admin.payroll.return-selected-to-bh', $period->id)); ?>">
                                <i class="fas fa-undo me-1"></i> Return Selected to BH
                            </button>
                            <button type="submit" class="btn btn-warning" formaction="<?php echo e(route('admin.payroll.return-to-bh', $period->id)); ?>" onclick="return confirm('Return the entire payroll and all payslips to BH?')">
                                <i class="fas fa-layer-group me-1"></i> Return All to BH
                            </button>
                        <?php elseif($isBranchHeadReviewer && $correctionStage === 'bh_review'): ?>
                            <button type="submit" class="btn btn-outline-primary" data-correction-action="<?php echo e(route('admin.payroll.return-selected-to-hr', $period->id)); ?>">
                                <i class="fas fa-undo me-1"></i> Return Selected to HR
                            </button>
                            <button type="submit" class="btn btn-primary" formaction="<?php echo e(route('admin.payroll.return-to-hr', $period->id)); ?>" onclick="return confirm('Return the entire payroll and all payslips to HR?')">
                                <i class="fas fa-layer-group me-1"></i> Return All to HR
                            </button>
                        <?php elseif($isHrReviewer && $correctionStage === 'hr_review'): ?>
                            <button type="submit" class="btn btn-outline-danger" data-correction-action="<?php echo e(route('admin.payroll.return-selected-to-fh', $period->id)); ?>">
                                <i class="fas fa-undo me-1"></i> Return Selected to FH
                            </button>
                            <button type="submit" class="btn btn-danger" formaction="<?php echo e(route('admin.payroll.return-to-fh', $period->id)); ?>" onclick="return confirm('Return the entire payroll and all payslips to FH for correction?')">
                                <i class="fas fa-layer-group me-1"></i> Return All to FH
                            </button>
                        <?php elseif($isFinanceHead && $correctionStage === 'fh_correction'): ?>
                            <button type="submit" class="btn btn-success" formaction="<?php echo e(route('admin.payroll.resubmit-corrected', $period->id)); ?>" onclick="return confirm('Send the corrected payroll back to HR for review?')">
                                <i class="fas fa-paper-plane me-1"></i> Send Corrected Payroll to HR
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
                <small class="text-muted d-block mt-2">Select one or more entries below before using a selected-payslip action.</small>
            </div>
        <?php endif; ?>
        <?php if($entries->isEmpty()): ?>
            <div class="card-body text-center py-5">
                <?php if(auth()->user()->role === 'finance_officer' && $correctionStage): ?>
                    <i class="fas fa-share-square fa-3x text-warning mb-3"></i>
                    <h5>Payroll returned for review or correction</h5>
                    <p class="text-muted mb-0">All returned payroll entries are hidden from Finance Officer view while the approval workflow is active.</p>
                <?php elseif(auth()->user()->isBranchAdmin() && in_array($correctionStage, ['hr_review', 'fh_correction'], true)): ?>
                    <i class="fas fa-share-square fa-3x text-warning mb-3"></i>
                    <h5>Payroll moved forward for review</h5>
                    <p class="text-muted mb-0">The returned payroll entries are now hidden from Branch Head view while the next review or correction stage is active.</p>
                <?php elseif(auth()->user()->isSuperAdmin() && $correctionStage === 'fh_correction'): ?>
                    <i class="fas fa-share-square fa-3x text-warning mb-3"></i>
                    <h5>Payroll returned to Finance Head</h5>
                    <p class="text-muted mb-0">The returned payroll entries are now hidden from HR view while Finance Head corrects them.</p>
                <?php else: ?>
                    <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
                    <h5>No payroll entries yet</h5>
                    <p class="text-muted mb-4">Get the approved DTRs for this payroll period to create editable payroll entries.</p>
                    <form action="<?php echo e(route('finance.payroll-generation.for-period', $period->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download me-1"></i> Get Approved DTRs
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 payroll-entries-table">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Employee</th>
                        <th>Basic Pay</th>
                        <th>Total Deductions</th>
                        <th>Gross Pay</th>
                        <th>SSS</th>
                        <th>PhilHealth</th>
                        <th>Pag-IBIG</th>
                        <th>Tax</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><input type="checkbox" class="payroll-entry-selector" value="<?php echo e($entry->id); ?>" aria-label="Select <?php echo e($entry->employee->first_name); ?> <?php echo e($entry->employee->last_name); ?>"></td>
                        <td><?php echo e($entry->employee->first_name); ?> <?php echo e($entry->employee->last_name); ?><br>
                            <small class="text-muted"><?php echo e($entry->employee->employee_number); ?></small>
                        </td>
                        <td>₱<?php echo e(number_format($entry->basic_pay, 2)); ?></td>
                        <td>₱<?php echo e(number_format($entry->total_deductions, 2)); ?></small></td>
                        <td><strong>₱<?php echo e(number_format($entry->gross_pay, 2)); ?></strong></td>
                        <td>₱<?php echo e(number_format($entry->sss_contribution, 2)); ?></td>
                        <td>₱<?php echo e(number_format($entry->philhealth_contribution, 2)); ?></td>
                        <td>₱<?php echo e(number_format($entry->pagibig_contribution, 2)); ?></td>
                        <td>₱<?php echo e(number_format($entry->withholding_tax, 2)); ?></small></td>
                        <td><strong class="text-success">₱<?php echo e(number_format($entry->net_pay, 2)); ?></strong></td>
                        <td>
                            <div class="payroll-entry-actions">
                            <span class="badge bg-<?php echo e($entry->status === 'approved' ? 'success' : ($entry->status === 'calculated' ? 'warning' : 'secondary')); ?>">
                                <?php echo e(ucfirst($entry->status)); ?>

                            </span>
                        </td>
                        <td>
                            <?php if($period->status === 'draft'): ?>
                                <a href="<?php echo e(route('admin.payroll.edit-entry', $entry->id)); ?>" class="btn btn-sm btn-warning mb-1">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php else: ?>
                                <a href="<?php echo e(route('admin.payroll.edit-entry', $entry->id)); ?>" class="btn btn-sm btn-outline-primary mb-1">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            <?php endif; ?>
                            <?php if(auth()->user()->role === 'finance_officer'): ?>
                                <a href="<?php echo e(route('admin.payroll.download-payslip', $entry->id)); ?>" class="btn btn-sm btn-success mb-1">
                                    <i class="fas fa-file-invoice-dollar"></i> Generate Payslip
                                </a>
                                <?php if($entry->status === 'approved'): ?>
                                    <form action="<?php echo e(route('admin.payroll.submit-payslip', $entry->id)); ?>" method="POST" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-sm btn-primary mb-1" onclick="return confirm('Submit this payslip to the employee email?')">
                                            <i class="fas fa-paper-plane"></i> <?php echo e($entry->payslip_sent_at ? 'Resend Payroll & Payslip' : 'Send Payroll & Payslip'); ?>

                                        </button>
                                    </form>
                                    <?php if($entry->payslip_sent_at): ?>
                                        <small class="d-block text-success"><i class="fas fa-check-circle"></i> Sent to <?php echo e($entry->payslip_sent_to); ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
(() => {
    const form = document.getElementById('selected-correction-form');
    if (!form) return;
    const inputContainer = document.getElementById('selected-entry-inputs');
    const selectedButtons = form.querySelectorAll('[data-correction-action]');
    const selectors = document.querySelectorAll('.payroll-entry-selector');

    selectedButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            const selected = Array.from(selectors).filter((checkbox) => checkbox.checked);
            if (!selected.length) {
                event.preventDefault();
                alert('Select at least one payroll entry first.');
                return;
            }
            form.action = button.dataset.correctionAction;
            inputContainer.innerHTML = selected.map((checkbox) =>
                `<input type="hidden" name="entry_ids[]" value="${checkbox.value}">`
            ).join('');
        });
    });
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\payroll\entries.blade.php ENDPATH**/ ?>