<?php $__env->startSection('title', 'Payroll Periods'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Payroll Management</h1>
        <?php if(auth()->user()->role === 'finance_head'): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPeriodModal">
                <i class="fas fa-plus"></i> New Payroll Period
            </button>
        <?php endif; ?>
    </div>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4" id="statsContainer">
        <div class="col text-center">
            <div class="card bg-primary text-white h-100 shadow-sm">
                <div class="card-body py-3">
                    <small class="text-uppercase">Total Periods</small>
                    <h3 class="mt-2" id="totalPeriods">0</h3>
                </div>
            </div>
        </div>
        <div class="col text-center">
            <div class="card bg-success text-white h-100 shadow-sm">
                <div class="card-body py-3">
                    <small class="text-uppercase">Completed</small>
                    <h3 class="mt-2" id="completedPeriods">0</h3>
                </div>
            </div>
        </div>
        <div class="col text-center">
            <div class="card bg-warning text-white h-100 shadow-sm">
                <div class="card-body py-3">
                    <small class="text-uppercase">Processing</small>
                    <h3 class="mt-2" id="processingPeriods">0</h3>
                </div>
            </div>
        </div>
        <div class="col text-center">
            <div class="card bg-secondary text-white h-100 shadow-sm">
                <div class="card-body py-3">
                    <small class="text-uppercase">Draft</small>
                    <h3 class="mt-2" id="draftPeriods">0</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center border-0 py-3">
            <div>
                <h5 class="mb-0">Payroll Periods</h5>
                <small class="text-muted">Manage your current payroll cycles and approvals.</small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" id="periodsContainer">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body.dark-mode .modal-content {
        background-color: #1e1e2f;
        color: #ffffff;
        border: 1px solid rgba(149, 100, 221, 0.5);
    }

    body.dark-mode .modal-header,
    body.dark-mode .modal-footer {
        border-color: rgba(149, 100, 221, 0.35);
        background-color: rgba(149, 100, 221, 0.08);
    }

    body.dark-mode .form-control,
    body.dark-mode .form-select,
    body.dark-mode select,
    body.dark-mode input,
    body.dark-mode textarea {
        background-color: #9564DD !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.25) !important;
    }

    body.dark-mode .form-control::placeholder,
    body.dark-mode .form-select::placeholder,
    body.dark-mode select::placeholder,
    body.dark-mode input::placeholder,
    body.dark-mode textarea::placeholder {
        color: rgba(255, 255, 255, 0.75) !important;
    }

    body.dark-mode .form-control:focus,
    body.dark-mode .form-select:focus,
    body.dark-mode select:focus,
    body.dark-mode input:focus,
    body.dark-mode textarea:focus {
        background-color: #9564DD !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.5) !important;
        box-shadow: 0 0 0 0.2rem rgba(149, 100, 221, 0.25) !important;
    }

    body.dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    #periodsContainer table th:last-child,
    #periodsContainer table td:last-child {
        min-width: 190px;
    }

    #periodsContainer .payroll-actions {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
        min-width: 170px;
    }

    #periodsContainer .payroll-actions > a,
    #periodsContainer .payroll-actions > form,
    #periodsContainer .payroll-actions > form > button {
        width: 100%;
    }

    #periodsContainer .payroll-actions > form {
        display: block !important;
        margin: 0;
    }

    #periodsContainer .payroll-actions .form-switch {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        min-height: 31px;
        padding-left: 2.5rem;
        white-space: nowrap;
    }

    #periodsContainer .payroll-actions .form-switch .form-check-input {
        margin-left: -2.5rem;
    }

    @media (max-width: 768px) {
        #periodsContainer table th:last-child,
        #periodsContainer table td:last-child {
            min-width: 170px;
        }
    }
</style>

<!-- Create Period Modal -->
<div class="modal fade" id="createPeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('admin.payroll.create-period')); ?>" id="createPeriodForm">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Create Payroll Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Enter Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. August 2026 Payroll" required>
                    </div>
                    <div class="mb-3">
                        <label>Select Branch</label>
                        <select name="branch_id" class="form-control" required>
                            <option value="">Choose a branch</option>
                            <?php $__currentLoopData = \App\Models\Branch::orderBy('branch_name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($branch->id); ?>"><?php echo e($branch->branch_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Period Type</label>
                        <select name="period_type" class="form-control" required>
                            <option value="semi_monthly">Semi-Monthly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

async function loadPayrollData() {
    try {
        const response = await fetch('/admin/payroll-data', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            updateStats(data);
            updatePeriodsTable(data.periods);
        }
    } catch (error) {
        console.error('Error loading payroll data:', error);
    }
}

function updateStats(data) {
    const html = `
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Periods</h6>
                    <h3>${data.total_periods || 0}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Completed</h6>
                    <h3>${data.completed_periods || 0}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Processing</h6>
                    <h3>${data.processing_periods || 0}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6>Draft</h6>
                    <h3>${data.draft_periods || 0}</h3>
                </div>
            </div>
        </div>
    `;
    document.getElementById('statsContainer').innerHTML = html;
}

function updatePeriodsTable(periods) {
    if (!periods || periods.length === 0) {
        document.getElementById('periodsContainer').innerHTML = `
            <div class="text-center py-4">No payroll periods found</div>
        `;
        return;
    }
    
    const csrfInput = `<input type="hidden" name="_token" value="${csrfToken}">`;

    let html = `
        <table class="table table-hover table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Period Code</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Payment Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    periods.forEach(period => {
        let statusBadge = '';
        if (period.status === 'draft') statusBadge = `<span class="badge bg-secondary">${period.status_label || 'Draft'}</span>`;
        else if (period.status === 'processing') statusBadge = '<span class="badge bg-info">Processing</span>';
        else if (period.status === 'completed') statusBadge = `<span class="badge bg-success">${period.status_label || 'Completed'}</span>`;
        else if (period.status === 'approved') statusBadge = `<span class="badge bg-primary">${period.status_label || 'Approved'}</span>`;
        const isApproved = period.status === 'approved';
        const isFinalized = ['approved', 'completed'].includes(period.status);
        const isApprovalSwitchState = ['draft', 'approved'].includes(period.status)
            || (period.status === 'completed' && period.can_return === true);
        const isAdminApprovalState = period.can_return === true && isFinalized;
        const canApprove = period.can_approve === true;
        const canProcess = period.can_process === true;
        const canSubmitToBranch = period.can_submit_to_branch === true;
        const canBranchApprove = period.can_branch_approve === true;
        const canSubmitToFinance = period.can_submit_to_finance === true;
        const canGeneratePayslip = period.can_generate_payslip === true;
        const canReturnToBh = period.can_return_to_bh === true;
        const canReviewBhReturn = period.can_review_bh_return === true;
        const canReviewHrReturn = period.can_review_hr_return === true;
        const submitAction = canSubmitToBranch ? period.submit_to_branch_url : period.process_url;
        const submitLabel = canSubmitToBranch ? 'Send to BH' : 'Send to HR';
        const submitButton = canProcess || canSubmitToBranch
            ? `<button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Send this payroll to the next approver?')">
                            <i class="fas fa-paper-plane"></i> ${submitLabel}
                        </button>`
            : `<button type="button" class="btn btn-sm btn-secondary" disabled title="Approve the payroll first">
                            <i class="fas fa-paper-plane"></i> Waiting for approval
                        </button>`;
        const financeOfficerAction = canGeneratePayslip
            ? `<a href="${period.entries_url}" class="btn btn-sm btn-success">
                            <i class="fas fa-file-invoice-dollar"></i> Generate Payslip
                        </a>`
            : '';
        const financeReturnAction = canReturnToBh
            ? `<a href="${period.entries_url}" class="btn btn-sm btn-warning">
                            <i class="fas fa-undo"></i> Review / Return to BH
                        </a>`
            : '';
        const branchReviewReturnAction = canReviewBhReturn
            ? `<a href="${period.entries_url}" class="btn btn-sm btn-primary">
                            <i class="fas fa-search"></i> BH Review / Return to HR
                        </a>`
            : '';
        const hrReviewReturnAction = canReviewHrReturn
            ? `<a href="${period.entries_url}" class="btn btn-sm btn-danger">
                            <i class="fas fa-search"></i> HR Review / Return to FH
                        </a>`
            : '';
        const approvalActions = canApprove && period.approval_mode === 'switch' && (isApprovalSwitchState || isAdminApprovalState) ? `
                    <form action="${isAdminApprovalState ? period.admin_approval_stage_url : (isApproved ? period.recheck_url : period.approve_url)}" method="POST" class="d-inline-flex align-items-center">
                        ${csrfInput}
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" aria-label="${isAdminApprovalState ? 'Change payroll approval stage' : (isApproved ? 'Re-check payroll' : 'Approve payroll')}" ${isAdminApprovalState ? (period.admin_approval_stage === 'hr_fd' ? 'checked' : '') : (isApproved ? 'checked' : '')} onchange="if (!confirm('${isAdminApprovalState ? 'Change payroll approval stage?' : (isApproved ? 'Return this payroll to Draft for re-checking?' : 'Approve this payroll period?')}')) { this.checked = !this.checked; return false; } this.form.submit();">
                            <span class="form-check-label fw-semibold ${isAdminApprovalState || isApproved ? 'text-primary' : 'text-success'}">${isAdminApprovalState ? (period.status_label || 'Approved FD') : (isApproved ? 'Approved' : 'Re-check')}</span>
                        </div>
                    </form>
        ` : canApprove && !isFinalized ? `
                    <form action="${period.approve_url}" method="POST" class="d-inline">
                        ${csrfInput}
                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this payroll period?')">
                            <i class="fas fa-check"></i> Approved
                        </button>
                    </form>
        ` : '';
        const returnAction = period.can_return === true && isFinalized ? `
                    <form action="${period.return_url}" method="POST" class="d-inline">
                        ${csrfInput}
                        <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Return this payroll period to Draft?')">
                            <i class="fas fa-undo"></i> Return
                        </button>
                    </form>
        ` : '';
        const branchApprovalAction = canBranchApprove ? `
                    <form action="${period.branch_approve_url}" method="POST">
                        ${csrfInput}
                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this payroll as Branch Head?')">
                            <i class="fas fa-check-circle"></i> Approve as BH
                        </button>
                    </form>
        ` : '';
        const branchFinanceSubmissionAction = canSubmitToFinance ? `
                    <form action="${period.branch_submit_finance_url}" method="POST">
                        ${csrfInput}
                        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Submit this approved payroll to the Finance Officer?')">
                            <i class="fas fa-share"></i> Submit to FO
                        </button>
                    </form>
        ` : '';

        html += `
            <tr>
                <td><strong>${period.name || period.period_code}</strong></td>
                <td>${period.branch_name || 'N/A'}</td>
                <td>${period.period_code}</td>
                <td>${period.start_date}</td>
                <td>${period.end_date}</td>
                <td>${period.payment_date}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="payroll-actions">
                    <a href="${period.entries_url}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                    ${canGeneratePayslip ? financeOfficerAction : (canProcess || canSubmitToBranch ? `<form action="${submitAction}" method="POST" class="d-inline">
                        ${csrfInput}
                        ${submitButton}
                    </form>` : '')}
                    ${financeReturnAction}
                    ${branchReviewReturnAction}
                    ${hrReviewReturnAction}
                    ${approvalActions}
                    ${branchApprovalAction}
                    ${branchFinanceSubmissionAction}
                    ${returnAction}
                    <a href="${period.download_url}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-download"></i> Download Payroll
                    </a>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `</tbody></table>`;
    document.getElementById('periodsContainer').innerHTML = html;
}

// Auto-refresh every 15 seconds
let refreshInterval = setInterval(loadPayrollData, 15000);
loadPayrollData();

// Handle form submission without page reload
document.getElementById('createPeriodForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    const response = await fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    
    const result = await response.json();
    
    if (result.success) {
        bootstrap.Modal.getInstance(document.getElementById('createPeriodModal')).hide();
        loadPayrollData(); // Refresh immediately
        alert('Payroll period created successfully!');
    } else {
        alert('Error: ' + result.error);
    }
});

// Stop when page hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(refreshInterval);
    } else {
        refreshInterval = setInterval(loadPayrollData, 15000);
        loadPayrollData();
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\payroll\periods.blade.php ENDPATH**/ ?>