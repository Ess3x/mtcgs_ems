<?php $__env->startSection('title', 'Review DTR'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="<?php echo e(route('admin.dtr.index')); ?>" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left"></i> Back to DTR Management
            </a>
            <h2 class="mb-2">DTR Review & Approval</h2>
            <p class="text-muted"><?php echo e($dtr->employeeProfile->first_name); ?> <?php echo e($dtr->employeeProfile->last_name); ?> (<?php echo e($dtr->employeeProfile->employee_number); ?>)</p>
        </div>
    </div>

    <!-- DTR Info & Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Period</p>
                    <h6 class="mb-3"><?php echo e($dtr->period_start->format('M d')); ?> - <?php echo e($dtr->period_end->format('M d, Y')); ?></h6>
                    <span class="badge bg-warning p-2"><?php echo e(ucfirst($dtr->status)); ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Hours</p>
                    <h4 class="mb-0"><?php echo e(number_format($stats['total_hours'], 1)); ?><small> hrs</small></h4>
                    <small class="text-muted">of <?php echo e($stats['working_days'] * 8); ?> hrs expected</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Days Present</p>
                    <h4 class="mb-0"><?php echo e($stats['days_present']); ?><small>/<?php echo e($stats['working_days']); ?></small></h4>
                    <small class="text-muted"><?php echo e($stats['days_absent']); ?> absent</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Late</p>
                    <?php if($stats['total_late_minutes'] > 0): ?>
                        <span class="badge bg-danger p-2">Total Late: <?php echo e($stats['total_late_minutes']); ?> min</span>
                    <?php else: ?>
                        <small class="text-muted">--</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Absent</p>
                    <h4 class="mb-0 text-danger"><?php echo e($stats['days_absent'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Paid Leave</p>
                    <h4 class="mb-0 text-info"><?php echo e($stats['total_paid_leave'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Leave Without Pay</p>
                    <h4 class="mb-0 text-warning"><?php echo e($stats['total_leave_without_pay'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Early Out</p>
                    <?php if(($stats['total_early_out_minutes'] ?? 0) > 0): ?>
                        <span class="badge bg-warning text-dark p-2"><?php echo e($stats['total_early_out_minutes']); ?> min</span>
                    <?php else: ?>
                        <small class="text-muted">--</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-signature me-2"></i>Employee E-Signature</h5>
        </div>
        <div class="card-body">
            <?php if(($dtr->employeeProfile->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($dtr->employeeProfile->signature_path)) || \App\Models\FinanceProfile::where('employee_profile_id', $dtr->employee_profile_id)->whereNotNull('signature_path')->exists() || \App\Models\AdminProfile::where('employee_profile_id', $dtr->employee_profile_id)->whereNotNull('signature_path')->exists()): ?>
                <img src="<?php echo e(route('profile.signature', $dtr->employeeProfile->id)); ?>" alt="Employee e-signature" style="max-width: 280px; max-height: 100px;">
                <p class="text-muted small mb-0 mt-2">Saved by the employee in Profile for DTR submission.</p>
            <?php else: ?>
                <span class="badge bg-warning text-dark">No e-signature saved</span>
                <p class="text-muted small mb-0 mt-2">The employee must save an e-signature before submitting a DTR.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daily Attendance Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th class="text-center">Time-In</th>
                                    <th class="text-center">Time-Out</th>
                                    <th class="text-center">Hours</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $current = $dtr->period_start->copy();
                                    $endDate = $dtr->period_end;
                                    $logsByDate = $attendanceLogs->keyBy(function($log) {
                                        return $log->attendance_date->format('Y-m-d');
                                    });
                                ?>

                                <?php while($current <= $endDate): ?>
                                    <?php
                                        $dateStr = $current->format('Y-m-d');
                                        $log = $logsByDate->get($dateStr);
                                        $isWeekend = in_array($current->dayOfWeek, [0, 6]);
                                        $approvedLeave = !$isWeekend ? $approvedLeaves->first(function ($leave) use ($current) {
                                            return $current->betweenIncluded($leave->start_date, $leave->end_date);
                                        }) : null;
                                        $isApprovedLeave = (bool) $approvedLeave;
                                        $isLWOP = $isApprovedLeave && (bool) $approvedLeave->is_absent;
                                        $isNoRecordYet = !$isWeekend && !$log && $dtr->status !== 'approved';
                                        $isPastMissedDay = !$isWeekend && !$log && $dtr->status === 'approved';
                                        $isAbsent = !$isWeekend && ($isPastMissedDay || ($log && strtolower((string) $log->status) === 'absent'));
                                        $attendanceStatus = $log ? $log->getDtrStatus() : null;
                                        $isLateTimeIn = $attendanceStatus && str_contains($attendanceStatus, 'Late');
                                        $isEarlyOut = $attendanceStatus && str_contains($attendanceStatus, 'Early Out');
                                        $hours = 0;
                                        if ($log && $log->am_in && $log->pm_out) {
                                            $hours = $log->am_in->diffInMinutes($log->pm_out) / 60;
                                        }
                                        $statusLetter = $isWeekend ? 'WKD' : (
                                            $isLWOP ? 'LWOP' : (
                                                $isApprovedLeave ? 'L' : (
                                                    $isAbsent ? 'A' : (
                                                        $isNoRecordYet ? '' : ($attendanceStatus ?: 'P')
                                                    )
                                                )
                                            )
                                        );
                                        $statusClass = $isWeekend ? 'bg-secondary' : (
                                            $isLWOP ? 'bg-danger' : (
                                                $isApprovedLeave ? 'bg-info' : (
                                                    $isAbsent ? 'bg-danger' : (
                                                        $isNoRecordYet ? 'bg-transparent text-transparent border-0' : ($attendanceStatus === 'Half Day' ? 'bg-warning text-dark' : ($isLateTimeIn || $isEarlyOut ? 'bg-danger' : 'bg-success'))
                                                    )
                                                )
                                            )
                                        );
                                    ?>
                                    <tr class="<?php echo e($isWeekend ? 'table-light' : ''); ?>">
                                        <td><?php echo e($current->format('M d (l)')); ?></td>
                                        <td class="text-center <?php echo e($isLateTimeIn && !$isApprovedLeave ? 'text-danger fw-bold' : ''); ?>"><?php echo e(!$isApprovedLeave && $log && $log->am_in ? $log->am_in->format('h:i A') : '--'); ?></td>
                                        <td class="text-center <?php echo e($isEarlyOut && !$isApprovedLeave ? 'text-danger fw-bold' : ''); ?>"><?php echo e(!$isApprovedLeave && $log && $log->pm_out ? $log->pm_out->format('h:i A') : '--'); ?></td>
                                        <td class="text-center">
                                            <?php if($log && !$isWeekend && !$isApprovedLeave): ?>
                                                <?php echo e(number_format($hours, 1)); ?> hrs
                                            <?php else: ?>
                                                --
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?php echo e($statusClass); ?>" style="min-width: 76px; max-width: 100%; border-radius: 0.4rem; display: inline-block; white-space: normal; overflow-wrap: anywhere; font-size: 0.75rem; font-weight: 800; padding: 0.45rem 0.55rem; line-height: 1.15;">
                                                <?php if($statusLetter !== ''): ?>
                                                    <?php echo e($statusLetter); ?>

                                                <?php endif; ?>
                                            </span>
                                            <?php if($log && in_array($log->override_status, ['pending_branch', 'pending_system_admin'], true)): ?>
                                                <div class="mt-2">
                                                    <small class="d-block text-muted"><?php echo e(ucfirst(str_replace('_', ' ', $log->override_status))); ?></small>
                                                    <small class="d-block">AM in: <?php echo e($log->am_in?->format('h:i A') ?? '--'); ?> -> <?php echo e($log->corrected_time_in?->format('h:i A') ?? '--'); ?></small>
                                                    <small class="d-block">PM in: <?php echo e($log->pm_in?->format('h:i A') ?? '--'); ?> -> <?php echo e($log->corrected_pm_in?->format('h:i A') ?? '--'); ?></small>
                                                    <small class="d-block">Time out: <?php echo e($log->pm_out?->format('h:i A') ?? '--'); ?> -> <?php echo e($log->corrected_time_out?->format('h:i A') ?? '--'); ?></small>
                                                    <small class="d-block mb-1"><?php echo e($log->override_reason); ?></small>
                                                    <?php if(Auth::user()->isBranchAdmin() && $log->override_status === 'pending_branch'): ?>
                                                        <form method="POST" action="<?php echo e(route('admin.dtr.attendance.approve-adjustment', $log->id)); ?>" class="d-inline">
                                                            <?php echo csrf_field(); ?>
                                                            <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                                        </form>
                                                        <form method="POST" action="<?php echo e(route('admin.dtr.attendance.reject-adjustment', $log->id)); ?>" class="d-inline">
                                                            <?php echo csrf_field(); ?>
                                                            <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                                        </form>
                                                    <?php elseif(Auth::user()->isSuperAdmin() && $log->override_status === 'pending_system_admin'): ?>
                                                        <form method="POST" action="<?php echo e(route('admin.dtr.attendance.approve-adjustment', $log->id)); ?>" class="d-inline">
                                                            <?php echo csrf_field(); ?>
                                                            <button class="btn btn-sm btn-success" type="submit">Final Approve</button>
                                                        </form>
                                                        <form method="POST" action="<?php echo e(route('admin.dtr.attendance.reject-adjustment', $log->id)); ?>" class="d-inline">
                                                            <?php echo csrf_field(); ?>
                                                            <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php $current->addDay(); ?>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Section -->
    <?php if(in_array($dtr->status, ['submitted', 'pending_system_admin'], true) && Auth::user()->isAdmin()): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <?php if($dtr->status === 'submitted'): ?>
                                Branch Head Approval
                            <?php else: ?>
                                System Administrator Review & Approval
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <form action="<?php echo e(route('admin.dtr.approve', $dtr->id)); ?>" method="POST" style="display: inline;">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-success btn-lg w-100" onclick="return confirm('<?php echo e($dtr->status === 'submitted' ? 'Approve this DTR and forward it to HR for review?' : 'Approve this DTR and forward it to the Finance Head for computation?'); ?>')">
                                        <i class="fas fa-check-circle"></i>
                                        <?php echo e($dtr->status === 'submitted' ? 'Approve & Forward to HR' : 'Submit to Finance Head'); ?>

                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-danger btn-lg w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="fas fa-times-circle"></i> Reject DTR
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal fade" id="rejectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject DTR</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="<?php echo e(route('admin.dtr.reject', $dtr->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks (Why reject?)</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="4" required></textarea>
                                <small class="text-muted">This will be sent to the employee</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reject & Send Back</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php elseif($dtr->status === 'pending_finance_head' && Auth::user()->isFinanceHead()): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Finance Head Computation</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Review the attendance records and computed totals above, then finalize this DTR for payroll.</p>
                        <form method="POST" action="<?php echo e(route('admin.dtr.compute', $dtr->id)); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-primary btn-lg w-100" onclick="return confirm('Compute and finalize this DTR for payroll?')">
                                <i class="fas fa-calculator me-2"></i> Compute DTR and Submit to Payroll
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif($dtr->status === 'submitted'): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="fas fa-lock"></i> Only administrators can approve or reject submitted DTRs.
                </div>
            </div>
        </div>
    <?php elseif($dtr->status === 'approved' && !$dtr->payrollEntry): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Next Step - Generate Payroll</h5>
                    </div>
                    <div class="card-body">
                        <p>This DTR has been approved. The Finance Officer can now generate a payroll entry from this DTR.</p>
                        <p class="text-muted mb-3">
                            <i class="fas fa-info-circle"></i> 
                            The system will automatically calculate:
                            <ul>
                                <li>Basic pay based on days worked</li>
                                <li>Overtime pay (25% premium)</li>
                                <li>Deductions for absences and late arrivals</li>
                                <li>Government contributions (SSS, PhilHealth, Pag-ibig)</li>
                                <li>Withholding tax</li>
                            </ul>
                        </p>
                        <a href="<?php echo e(route('finance.payroll-generation.ready-dtrs')); ?>" class="btn btn-success btn-lg">
                            <i class="fas fa-arrow-right"></i> Go to Payroll Generation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif($dtr->status === 'approved' && $dtr->payrollEntry): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Payroll Generated</h5>
                    </div>
                    <div class="card-body">
                        <p>✅ Payroll entry has been generated from this DTR.</p>
                        <a href="<?php echo e(route('finance.payroll-generation.entry', $dtr->payrollEntry->id)); ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Payroll Entry
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\dtr\show.blade.php ENDPATH**/ ?>