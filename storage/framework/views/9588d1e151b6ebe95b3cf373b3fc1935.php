

<?php $__env->startSection('title', 'Cash Charges Management'); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .cash-charges-page .evidence-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.35rem 0.65rem;
            border: 1px solid rgba(128, 0, 128, 0.35);
            border-radius: 0.5rem;
            background: rgba(128, 0, 128, 0.08);
            color: #800080;
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.25;
            text-decoration: none;
            white-space: nowrap;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .cash-charges-page .evidence-link:hover {
            transform: translateY(-1px);
            border-color: #800080;
            background: #800080;
            color: #fff;
        }

        .cash-charges-page .evidence-link:focus-visible {
            outline: 3px solid rgba(128, 0, 128, 0.3);
            outline-offset: 2px;
        }

        body.dark-mode .cash-charges-page .evidence-link {
            border-color: rgba(192, 132, 252, 0.45);
            background: rgba(126, 34, 206, 0.18);
            color: #e9d5ff;
        }

        body.dark-mode .cash-charges-page .evidence-link:hover {
            border-color: #c084fc;
            background: #7e22ce;
            color: #fff;
        }

        body.dark-mode .cash-charges-page .table tbody tr,
        body.dark-mode .cash-charges-page .table tbody td {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        body.dark-mode .cash-charges-page .table.table-hover tbody tr:hover,
        body.dark-mode .cash-charges-page .table.table-hover tbody tr:hover td {
            background-color: #273449 !important;
        }
    </style>

    <div class="container-fluid py-4 cash-charges-page">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="mb-1">Cash Charges Management</h2>
                <p class="text-muted mb-0">Set and update cash charge balances for active users.</p>
            </div>
        </div>

        <?php
            $isMyChargesScope = request('scope') === 'my' || Auth::user()->role === 'employee';
            $isEmployeeRole = Auth::user()->role === 'employee';
            $isBranchAdminRole = Auth::user()->role === 'admin' && (Auth::user()->admin_type ?? '') === 'branch_admin';
            $canAddCharges = Auth::user()->role === 'admin' && Auth::user()->admin_type === 'super_admin'
                || Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin'
                || Auth::user()->role === 'branch_head'
                || Auth::user()->role === 'finance_head';
            $showManagementSections = !($isEmployeeRole);
            $showMyCharges = in_array(Auth::user()->role, ['employee', 'finance_officer', 'finance_head', 'branch_head'], true)
                || ($isBranchAdminRole && $isMyChargesScope);
        ?>

        <?php if($showMyCharges): ?>
            <?php
                $profileForSummary = Auth::user()->getEmployeeProfile() ?? Auth::user()->getAdminProfile()?->employeeProfile ?? null;
                $balanceForSummary = $profileForSummary
                    ? \App\Models\LeaveBalance::firstOrCreate([
                        'employee_profile_id' => $profileForSummary->id,
                        'year' => now()->year,
                    ], [
                        'sick_leave_used' => 0,
                        'vacation_leave_used' => 0,
                        'emergency_leave_used' => 0,
                        'birthday_leave_used' => 0,
                        'cash_charge_used' => 0,
                        'maternity_leave_used' => 0,
                        'paternity_leave_used' => 0,
                    ])
                    : null;
                $pendingChargeRequest = $myCharges
                    ->whereIn('status', ['pending_branch_admin', 'pending_super_admin'])
                    ->sort(function ($left, $right) {
                        $leftCreated = $left->created_at?->timestamp ?? 0;
                        $rightCreated = $right->created_at?->timestamp ?? 0;

                        if ($leftCreated === $rightCreated) {
                            return ($right->id ?? 0) <=> ($left->id ?? 0);
                        }

                        return $rightCreated <=> $leftCreated;
                    })
                    ->first();
                $latestApprovedCharge = $myCharges
                    ->filter(function ($charge) {
                        return $charge->status === 'approved';
                    })
                    ->sort(function ($left, $right) {
                        $leftApproved = $left->approved_at?->timestamp ?? $left->created_at?->timestamp ?? 0;
                        $rightApproved = $right->approved_at?->timestamp ?? $right->created_at?->timestamp ?? 0;

                        if ($leftApproved === $rightApproved) {
                            return ($right->id ?? 0) <=> ($left->id ?? 0);
                        }

                        return $rightApproved <=> $leftApproved;
                    })
                    ->first();
                $baseChargeBalance = (float) ($balanceForSummary->cash_charge_total ?? 0);
                $branchAdminMyCharges = $myCharges
                    ->filter(fn ($charge) => in_array($charge->status, ['pending', 'pending_branch_admin', 'pending_super_admin', 'rejected'], true))
                    ->values();
                $approvedMyCharges = $myCharges
                    ->filter(function ($charge) {
                        return $charge->status === 'approved';
                    })
                    ->sort(function ($left, $right) {
                        $leftApproved = $left->approved_at?->timestamp ?? $left->created_at?->timestamp ?? 0;
                        $rightApproved = $right->approved_at?->timestamp ?? $right->created_at?->timestamp ?? 0;

                        return $rightApproved <=> $leftApproved ?: (($right->id ?? 0) <=> ($left->id ?? 0));
                    })
                    ->values();
                $totalChargeBalance = $latestApprovedCharge
                    ? (float) ($latestApprovedCharge->remaining_balance ?? $latestApprovedCharge->amount)
                    : ($baseChargeBalance > 0 ? $baseChargeBalance : ($pendingChargeRequest ? (float) $pendingChargeRequest->amount : 0));
                $installmentPerCutoff = $latestApprovedCharge && $latestApprovedCharge->installment_per_cutoff !== null
                    ? (float) $latestApprovedCharge->installment_per_cutoff
                    : ($baseChargeBalance > 0 ? $baseChargeBalance / 2 : ($pendingChargeRequest && $pendingChargeRequest->installment_per_cutoff !== null ? (float) $pendingChargeRequest->installment_per_cutoff : 0));
                $archivedMyCharges = $archivedMyCharges ?? collect();
            ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <strong>My Charges</strong>
                    <span class="badge bg-primary-subtle text-primary"><?php echo e($myCharges->count()); ?> record(s)</span>
                </div>
                <div class="card-body pb-2">
                    <form method="POST" action="<?php echo e($profileForSummary ? route('admin.cash-charges.request-update', $profileForSummary) : '#'); ?>" class="row g-3 align-items-end">
                        <?php echo csrf_field(); ?>
                        <div class="col-md-6">
                            <label class="form-label small text-muted mb-1">Total Charge Balance (Amount)</label>
                            <input type="number" step="0.01" min="0" max="50000" id="my-charge-total" name="cash_charge_total" class="form-control form-control-lg" value="<?php echo e(number_format($totalChargeBalance, 2, '.', '')); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Installment / Cut-off</label>
                            <input type="number" step="0.01" min="0" max="50000" id="my-charge-installment" name="installment_per_cutoff" class="form-control form-control-lg" value="<?php echo e(number_format($installmentPerCutoff, 2, '.', '')); ?>">
                        </div>
                        <div class="col-md-2 d-flex justify-content-end">
                            <?php if($profileForSummary): ?>
                                <button type="submit" class="btn btn-primary w-100">Save</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <?php if($branchAdminMyCharges->isNotEmpty()): ?>
                    <div class="border-top pt-3 mt-3">
                        <h5 class="mb-3">Applied by Branch Admin</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Branch</th>
                                        <th>Amount</th>
                                        <th>Installment Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $branchAdminMyCharges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $charge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $displayStatus = match ($charge->status) {
                                                'pending_branch_admin' => 'Pending Branch Admin',
                                                'pending_super_admin' => 'Pending Super Admin',
                                                'rejected' => 'Rejected',
                                                default => ucfirst(str_replace('_', ' ', $charge->status ?? 'pending')),
                                            };
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($charge->employeeProfile?->first_name); ?> <?php echo e($charge->employeeProfile?->last_name); ?></div>
                                                <small class="text-muted"><?php echo e($charge->employeeProfile?->employee_number); ?></small>
                                            </td>
                                            <td><?php echo e($charge->employeeProfile?->branch?->branch_name ?? 'N/A'); ?></td>
                                            <td>₱<?php echo e(number_format((float) $charge->amount, 2)); ?></td>
                                            <td>₱<?php echo e(number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2)); ?></td>
                                            <td><?php echo e($charge->reason); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo e($charge->status === 'approved' ? 'success' : ($charge->status === 'rejected' ? 'danger' : 'warning text-dark')); ?>">
                                                    <?php echo e($displayStatus); ?>

                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if($approvedMyCharges->isNotEmpty()): ?>
                    <div class="border-top pt-3 mt-3">
                        <h5 class="mb-3">Approved by Super Admin</h5>
                        <h6 class="text-muted mb-2">Installment Details</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Branch</th>
                                        <th>Installment Amount</th>
                                        <th>Reason</th>
                                        <th>Evidence</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $approvedMyCharges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $charge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($charge->employeeProfile?->first_name); ?> <?php echo e($charge->employeeProfile?->last_name); ?></div>
                                                <small class="text-muted"><?php echo e($charge->employeeProfile?->employee_number); ?></small>
                                            </td>
                                            <td><?php echo e($charge->employeeProfile?->branch?->branch_name ?? 'N/A'); ?></td>
                                            <td>₱<?php echo e(number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2)); ?></td>
                                            <td><?php echo e($charge->reason); ?></td>
                                            <td>
                                                <?php if($charge->evidence_path): ?>
                                                    <a class="evidence-link" href="<?php echo e(route('admin.cash-charges.evidence', $charge)); ?>" target="_blank" rel="noopener" title="View evidence image" aria-label="View evidence image">
                                                        <i class="fas fa-eye" aria-hidden="true"></i><span>View</span>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Approved</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <h6 class="text-muted mb-2 mt-4">Amount Details</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Branch</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Evidence</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $approvedMyCharges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $charge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($charge->employeeProfile?->first_name); ?> <?php echo e($charge->employeeProfile?->last_name); ?></div>
                                                <small class="text-muted"><?php echo e($charge->employeeProfile?->employee_number); ?></small>
                                            </td>
                                            <td><?php echo e($charge->employeeProfile?->branch?->branch_name ?? 'N/A'); ?></td>
                                            <td>₱<?php echo e(number_format((float) $charge->amount, 2)); ?></td>
                                            <td><?php echo e($charge->reason); ?></td>
                                            <td>
                                                <?php if($charge->evidence_path): ?>
                                                    <a class="evidence-link" href="<?php echo e(route('admin.cash-charges.evidence', $charge)); ?>" target="_blank" rel="noopener" title="View evidence image" aria-label="View evidence image">
                                                        <i class="fas fa-eye" aria-hidden="true"></i><span>View</span>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-success">Approved</span></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(Auth::user()->role === 'admin' && (Auth::user()->admin_type ?? '') === 'super_admin' && $archivedMyCharges->isNotEmpty()): ?>
                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0">Archives</h5>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#my-charges-archives">
                                View Archives
                            </button>
                        </div>
                        <div class="collapse" id="my-charges-archives">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee</th>
                                            <th>Branch</th>
                                            <th>Amount</th>
                                            <th>Installment Amount</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $archivedMyCharges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $charge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?php echo e($charge->employeeProfile?->first_name); ?> <?php echo e($charge->employeeProfile?->last_name); ?></div>
                                                    <small class="text-muted"><?php echo e($charge->employeeProfile?->employee_number); ?></small>
                                                </td>
                                                <td><?php echo e($charge->employeeProfile?->branch?->branch_name ?? 'N/A'); ?></td>
                                                <td>₱<?php echo e(number_format((float) $charge->amount, 2)); ?></td>
                                                <td>₱<?php echo e(number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2)); ?></td>
                                                <td><?php echo e($charge->reason); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo e($charge->status === 'approved' ? 'success' : 'danger'); ?>">
                                                        <?php echo e($charge->status === 'approved' ? 'Approved' : 'Rejected'); ?>

                                                    </span>
                                                </td>
                                                <td>
                                                    <form method="POST" action="<?php echo e(route('admin.cash-charges.unarchive', $charge)); ?>" class="d-inline">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit" class="btn btn-outline-success btn-sm">Un-Archived</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if($branchAdminMyCharges->isEmpty() && $approvedMyCharges->isEmpty() && $archivedMyCharges->isEmpty()): ?>
                    <div class="text-center text-muted py-4">No charges submitted under your account yet.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if($canAddCharges && $showManagementSections && !$isMyChargesScope): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <strong>Add Charges</strong>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#cash-charge-form-wrapper">
                        <i class="fas fa-plus me-1"></i> Add Charges
                    </button>
                </div>
                <div class="collapse" id="cash-charge-form-wrapper">
                    <div class="card-body">
                        <form method="POST" action="<?php echo e(route('admin.cash-charges.store')); ?>" class="row g-3 align-items-end" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <div class="col-md-4">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">Select employee</option>
                                    <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($employee->id); ?>"><?php echo e($employee->first_name); ?> <?php echo e($employee->last_name); ?> - <?php echo e($employee->branch?->branch_name ?? 'N/A'); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="50000" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" class="form-control" maxlength="1000" placeholder="Enter reason" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cash-charge-evidence" class="form-label">Upload Evidence</label>
                                <input id="cash-charge-evidence" type="file" name="evidence" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <small class="form-text text-muted">Image only, up to 5 MB.</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Submit Charge Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if($showManagementSections && !$isMyChargesScope): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <strong>Cash Charge Requests</strong>
                    <span class="badge bg-primary-subtle text-primary"><?php echo e($cashCharges->count()); ?> requests</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th>Amount</th>
                                <th>Installment Amount</th>
                                <th>Reason</th>
                                <th>Evidence</th>
                                <th>Status</th>
                                <?php if(Auth::user()->role === 'admin' && in_array(Auth::user()->admin_type ?? '', ['branch_admin', 'super_admin'], true)): ?>
                                    <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $cashCharges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $charge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $displayStatus = match ($charge->status) {
                                        'pending' => 'Pending Branch Admin',
                                        'pending_branch_admin' => 'Pending Branch Admin',
                                        'pending_super_admin' => 'Pending Super Admin',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                        default => ucfirst(str_replace('_', ' ', $charge->status ?? 'pending')),
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($charge->employeeProfile?->first_name); ?> <?php echo e($charge->employeeProfile?->last_name); ?></div>
                                        <small class="text-muted"><?php echo e($charge->employeeProfile?->employee_number); ?></small>
                                    </td>
                                    <td><?php echo e($charge->employeeProfile?->branch?->branch_name ?? 'N/A'); ?></td>
                                    <td>₱<?php echo e(number_format((float) $charge->amount, 2)); ?></td>
                                    <td>₱<?php echo e(number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2)); ?></td>
                                    <td><?php echo e($charge->reason); ?></td>
                                    <td>
                                        <?php if($charge->evidence_path): ?>
                                            <a class="evidence-link" href="<?php echo e(route('admin.cash-charges.evidence', $charge)); ?>" target="_blank" rel="noopener" title="View evidence image" aria-label="View evidence image">
                                                <i class="fas fa-eye" aria-hidden="true"></i><span>View</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo e($charge->status === 'approved' ? 'success' : ($charge->status === 'rejected' ? 'danger' : 'warning text-dark')); ?>">
                                            <?php echo e($displayStatus); ?>

                                        </span>
                                    </td>
                                    <?php if(Auth::user()->role === 'admin' && in_array(Auth::user()->admin_type ?? '', ['branch_admin', 'super_admin'], true)): ?>
                                        <td>
                                            <?php
                                                $isReviewableByCurrentAdmin = match (Auth::user()->admin_type ?? '') {
                                                    'branch_admin' => in_array($charge->status, ['pending', 'pending_branch_admin'], true),
                                                    'super_admin' => $charge->status === 'pending_super_admin',
                                                    default => false,
                                                };
                                            ?>
                                            <?php if($charge->status === 'archived'): ?>
                                                <span class="text-muted small">Archived</span>
                                            <?php elseif($isReviewableByCurrentAdmin): ?>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <form method="POST" action="<?php echo e(route('admin.cash-charges.approve', $charge)); ?>" class="d-flex gap-2 align-items-center">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="decision" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" name="decision" value="reject">Reject</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex flex-column gap-2 align-items-start">
                                                    <span class="text-muted small"><?php echo e($charge->approver ? 'Reviewed by ' . $charge->approver->name : 'Processed'); ?></span>
                                                    <?php if(Auth::user()->admin_type === 'super_admin' && $charge->status !== 'archived'): ?>
                                                        <form method="POST" action="<?php echo e(route('admin.cash-charges.archive', $charge)); ?>">
                                                            <?php echo csrf_field(); ?>
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm">Delete</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="<?php echo e((Auth::user()->role === 'admin' && in_array(Auth::user()->admin_type ?? '', ['branch_admin', 'super_admin'], true)) ? 8 : 7); ?>" class="text-center text-muted py-4">No cash charge requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if(Auth::user()->role === 'admin' && (Auth::user()->admin_type ?? '') === 'super_admin'): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <strong>Archives</strong>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#cash-charge-archives-list">
                            View Archives
                        </button>
                    </div>
                    <div class="collapse" id="cash-charge-archives-list">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Branch</th>
                                        <th>Amount</th>
                                        <th>Installment Amount</th>
                                        <th>Reason</th>
                                        <th>Evidence</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $archivedCashCharges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $charge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($charge->employeeProfile?->first_name); ?> <?php echo e($charge->employeeProfile?->last_name); ?></div>
                                                <small class="text-muted"><?php echo e($charge->employeeProfile?->employee_number); ?></small>
                                            </td>
                                            <td><?php echo e($charge->employeeProfile?->branch?->branch_name ?? 'N/A'); ?></td>
                                            <td>₱<?php echo e(number_format((float) $charge->amount, 2)); ?></td>
                                            <td>₱<?php echo e(number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2)); ?></td>
                                            <td><?php echo e($charge->reason); ?></td>
                                            <td>
                                                <?php if($charge->evidence_path): ?>
                                                    <a class="evidence-link" href="<?php echo e(route('admin.cash-charges.evidence', $charge)); ?>" target="_blank" rel="noopener" title="View evidence image" aria-label="View evidence image">
                                                        <i class="fas fa-eye" aria-hidden="true"></i><span>View</span>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo e($charge->status === 'approved' ? 'success' : 'danger'); ?>">
                                                    <?php echo e($charge->status === 'approved' ? 'Approved' : 'Rejected'); ?>

                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="<?php echo e(route('admin.cash-charges.unarchive', $charge)); ?>" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-outline-success btn-sm">Un-Archived</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No archived cash charge requests found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\cash-charges.blade.php ENDPATH**/ ?>