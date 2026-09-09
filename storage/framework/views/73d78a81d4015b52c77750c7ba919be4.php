<?php $__env->startSection('title', 'DTR / Attendance'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .dtr-management-page .nav-tabs .nav-link {
        color: #334155 !important;
        background-color: #e2e8f0;
        border-color: #cbd5e1;
    }

    .dtr-management-page .nav-tabs .nav-link:hover,
    .dtr-management-page .nav-tabs .nav-link.active {
        color: #0f172a !important;
        background-color: #ffffff;
        border-color: #cbd5e1 #cbd5e1 #ffffff;
    }

    body.dark-mode .dtr-management-page .nav-tabs .nav-link {
        color: #e2e8f0 !important;
        background-color: #1e293b;
        border-color: #475569;
    }

    body.dark-mode .dtr-management-page .nav-tabs .nav-link:hover,
    body.dark-mode .dtr-management-page .nav-tabs .nav-link.active {
        color: #ffffff !important;
        background-color: #334155;
        border-color: #64748b #64748b #334155;
    }
</style>

<div class="container-fluid py-4 dtr-management-page" style="background-color: #0a1221; min-height: 100vh;">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #4f8fe9 0%, #3a73d8 100%); color: white;">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h2 class="mb-1 text-white">DTR / Attendance</h2>
                            <p class="text-white-50 mb-0">Review and manage attendance records and DTR updates for payroll and monitoring.</p>
                        </div>
                        <div class="text-end mt-2 mt-md-0">
                            <span class="badge bg-white text-primary py-2 px-3">Updated <?php echo e(now()->format('M d, Y')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #6a73ff, #4f8fe9);">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Total DTRs</p>
                            <h3 class="mb-0 text-white"><?php echo e($totalDTRs); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #f7b731, #f39c12);">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Pending</p>
                            <h3 class="mb-0 text-warning"><?php echo e($pendingCount); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #32c787, #28a745);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Approved</p>
                            <h3 class="mb-0 text-success"><?php echo e($approvedDTRsCount); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #ff6b6b, #dc3545);">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Rejected</p>
                            <h3 class="mb-0 text-danger"><?php echo e($rejectedDTRs); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="background: rgba(10,18,33,0.55); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-header bg-transparent border-0 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 py-3">
                    <div>
                        <p class="text-white-50 mb-0">Browse pending and approved DTRs in one place.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark py-2 px-3">Pending <?php echo e($pendingCount); ?></span>
                        <span class="badge bg-success py-2 px-3">Approved <?php echo e($approvedDTRsCount); ?></span>
                        <?php if(Auth::user()->isBranchAdmin() || Auth::user()->isSuperAdmin()): ?>
                            <a href="<?php echo e(route('admin.dtr.export-submitted')); ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-file-excel me-1"></i> Export DTR Excel
                            </a>
                            <?php if(Auth::user()->isBranchAdmin() && $pendingCount > 0): ?>
                                <form method="POST" action="<?php echo e(route('admin.dtr.submit-all-to-hr')); ?>" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Submit all submitted branch DTRs and the combined Excel report to HR?')">
                                        <i class="fas fa-paper-plane me-1"></i> Submit All to HR
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-3">
                    <ul class="nav nav-tabs mb-3" role="tablist" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-white" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-tab-pane" type="button" role="tab" aria-controls="pending-tab-pane" aria-selected="true">Pending DTRs</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved-tab-pane" type="button" role="tab" aria-controls="approved-tab-pane" aria-selected="false">Approved DTRs</button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pending-tab-pane" role="tabpanel" aria-labelledby="pending-tab">
                            <?php if($pendingDTRs->count() > 0): ?>
                                <?php
                                    $pendingGroups = Auth::user()->isSuperAdmin() ? $pendingDTRs->groupBy(fn($dtr) => $dtr->employeeProfile && $dtr->employeeProfile->branch ? $dtr->employeeProfile->branch->branch_name : 'Unassigned Branch') : [null => $pendingDTRs];
                                ?>

                                <?php $__currentLoopData = $pendingGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branchName => $branchDTRs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="mb-4">
                                        <?php if(Auth::user()->isSuperAdmin()): ?>
                                            <div class="fw-bold text-white mb-2 border-bottom pb-2"><?php echo e($branchName); ?></div>
                                        <?php endif; ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover table-sm align-middle mb-0" style="color: white;">
                                                <thead style="background-color: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.1);">
                                                    <tr>
                                                        <th>Employee</th>
                                                        <th>Period</th>
                                                        <th class="text-center">Days Present</th>
                                                        <th class="text-center">Total Hours</th>
                                                        <th class="text-center">Overtime</th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $__currentLoopData = $branchDTRs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dtr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <tr style="border-color: rgba(255,255,255,0.05);">
                                                            <td class="text-wrap" style="min-width: 180px;">
                                                                <strong><?php echo e($dtr->employeeProfile->first_name); ?> <?php echo e($dtr->employeeProfile->last_name); ?></strong>
                                                                <div class="text-white-50 small"><?php echo e($dtr->employeeProfile->employee_number); ?></div>
                                                            </td>
                                                            <td class="text-wrap" style="min-width: 160px;"><?php echo e($dtr->period_start->format('M d')); ?> - <?php echo e($dtr->period_end->format('M d, Y')); ?></td>
                                                            <td class="text-center text-nowrap"><?php echo e($dtr->getDaysPresent()); ?>/<?php echo e($dtr->getWorkingDays()); ?></td>
                                                            <td class="text-center text-nowrap"><?php echo e(number_format($dtr->getTotalHoursWorked(), 1)); ?> hrs</td>
                                                            <td class="text-center text-nowrap">
                                                                <?php $ot = $dtr->getTotalOvertimeHours(); ?>
                                                                <?php if($ot > 0): ?>
                                                                    <span class="badge bg-warning"><?php echo e(number_format($ot, 1)); ?> hrs</span>
                                                                <?php else: ?>
                                                                    <span class="text-white-50">--</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                                    <a href="<?php echo e(route('admin.dtr.show', $dtr->id)); ?>" class="btn btn-sm btn-outline-info" title="Review">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <?php if($canApprove): ?>
                                                                        <form action="<?php echo e(route('admin.dtr.approve', $dtr->id)); ?>" method="POST" class="d-inline">
                                                                            <?php echo csrf_field(); ?>
                                                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                                                <i class="fas fa-check"></i>
                                                                            </button>
                                                                        </form>
                                                                        <form action="<?php echo e(route('admin.dtr.reject', $dtr->id)); ?>" method="POST" class="d-inline">
                                                                            <?php echo csrf_field(); ?>
                                                                            <input type="hidden" name="remarks" value="Rejected by administrator" />
                                                                            <button type="submit" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Reject this DTR and send back to employee?')">
                                                                                <i class="fas fa-times"></i>
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                <div class="d-flex justify-content-center mt-3">
                                    <?php echo e($pendingDTRs->links()); ?>

                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0" style="background-color: rgba(23,162,184,0.2); border-color: #17a2b8; color: #b8daff;">
                                    <i class="fas fa-info-circle"></i> No pending DTRs awaiting approval.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Approved DTRs Tab -->
                        <div class="tab-pane fade" id="approved-tab-pane" role="tabpanel" aria-labelledby="approved-tab">
                            <?php if($approvedDTRs->count() > 0): ?>
                                <?php
                                    $approvedGroups = $approvedDTRs->groupBy(fn($dtr) => $dtr->employeeProfile && $dtr->employeeProfile->branch ? $dtr->employeeProfile->branch->branch_name : 'Unassigned Branch');
                                ?>

                                <?php $__currentLoopData = $approvedGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branchName => $branchDTRs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="mb-4">
                                        <div class="fw-bold text-white mb-2 border-bottom pb-2">
                                            <i class="fas fa-building me-2 text-info"></i><?php echo e($branchName); ?>

                                        </div>
                                        <?php
                                            $periodGroups = $branchDTRs->groupBy(fn($dtr) => $dtr->period_start->format('Y-m-d') . '|' . $dtr->period_end->format('Y-m-d'));
                                        ?>
                                        <?php $__currentLoopData = $periodGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $periodKey => $periodDTRs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php $period = $periodDTRs->first(); ?>
                                            <div class="mb-3">
                                                <?php $periodPanelId = 'approved-period-' . md5($branchName . '-' . $periodKey); ?>
                                                <button class="btn btn-link text-decoration-none text-start text-white-50 small fw-semibold p-0 mb-2 w-100"
                                                        type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#<?php echo e($periodPanelId); ?>"
                                                        aria-expanded="false"
                                                        aria-controls="<?php echo e($periodPanelId); ?>">
                                                    <i class="fas fa-calendar-alt me-2"></i>
                                                    Period: <?php echo e($period->period_start->format('M d')); ?> - <?php echo e($period->period_end->format('M d, Y')); ?>

                                                    <i class="fas fa-chevron-down ms-2"></i>
                                                    <span class="badge bg-secondary ms-2"><?php echo e($periodDTRs->count()); ?> DTR<?php echo e($periodDTRs->count() === 1 ? '' : 's'); ?></span>
                                                </button>
                                                <div id="<?php echo e($periodPanelId); ?>" class="collapse">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover table-sm align-middle mb-0" style="color: white;">
                                                            <thead style="background-color: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.1);">
                                                                <tr>
                                                                    <th>Employee</th>
                                                                    <th>Period</th>
                                                                    <th class="text-center">Days Present</th>
                                                                    <th class="text-center">Total Hours</th>
                                                                    <th class="text-center">Overtime</th>
                                                                    <th class="text-center">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php $__currentLoopData = $periodDTRs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dtr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <tr style="border-color: rgba(255,255,255,0.05);">
                                                                        <td class="text-wrap" style="min-width: 180px;">
                                                                            <strong><?php echo e($dtr->employeeProfile->first_name); ?> <?php echo e($dtr->employeeProfile->last_name); ?></strong>
                                                                            <div class="text-white-50 small"><?php echo e($dtr->employeeProfile->employee_number); ?></div>
                                                                        </td>
                                                                        <td class="text-wrap" style="min-width: 160px;"><?php echo e($dtr->period_start->format('M d')); ?> - <?php echo e($dtr->period_end->format('M d, Y')); ?></td>
                                                                        <td class="text-center text-nowrap"><?php echo e($dtr->getDaysPresent()); ?>/<?php echo e($dtr->getWorkingDays()); ?></td>
                                                                        <td class="text-center text-nowrap"><?php echo e(number_format($dtr->getTotalHoursWorked(), 1)); ?> hrs</td>
                                                                        <td class="text-center text-nowrap">
                                                                            <?php $ot = $dtr->getTotalOvertimeHours(); ?>
                                                                            <?php if($ot > 0): ?>
                                                                                <span class="badge bg-warning"><?php echo e(number_format($ot, 1)); ?> hrs</span>
                                                                            <?php else: ?>
                                                                                <span class="text-white-50">--</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                                                <a href="<?php echo e(route('admin.dtr.show', $dtr->id)); ?>" class="btn btn-sm btn-outline-info" title="View Details">
                                                                                    <i class="fas fa-eye"></i>
                                                                                </a>
                                                                                <?php if(Auth::user()->isFinanceOfficer()): ?>
                                                                                    <a href="<?php echo e(route('finance.payroll-generation.ready-dtrs')); ?>" class="btn btn-sm btn-success" title="Generate Payroll">
                                                                                        <i class="fas fa-calculator"></i>
                                                                                    </a>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                <div class="d-flex justify-content-center mt-3">
                                    <?php echo e($approvedDTRs->links()); ?>

                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0" style="background-color: rgba(23,162,184,0.2); border-color: #17a2b8; color: #b8daff;">
                                    <i class="fas fa-info-circle"></i> No approved DTRs ready for payroll generation.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\dtr\index.blade.php ENDPATH**/ ?>