<?php $__env->startSection('title', 'View DTR'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dtr-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="dtr-header-copy">
                    <a href="<?php echo e(route('employee.dtr.index')); ?>" class="btn btn-outline-secondary btn-sm mb-3">
                        <i class="fas fa-arrow-left"></i> Back to DTR List
                    </a>
                    <h2 class="mb-2">DTR Details</h2>
                    <p class="text-muted mb-0"><?php echo e($employeeProfile->first_name); ?> <?php echo e($employeeProfile->last_name); ?> (<?php echo e($employeeProfile->employee_number); ?>)</p>
                </div>
                <div class="dtr-action-group">
                    <div class="d-flex gap-2 mb-2 justify-content-end flex-wrap">
                        <a href="<?php echo e(route('employee.dtr.download', $dtr->id)); ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-pdf me-1"></i> Download PDF
                        </a>
                        <a href="<?php echo e(route('employee.dtr.download-excel', $dtr->id)); ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i> Download Excel
                        </a>
                        <?php if($previousDTR): ?>
                            <a href="<?php echo e(route('employee.dtr.show', $previousDTR->id)); ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-chevron-left"></i> Previous DTR
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                <i class="fas fa-chevron-left"></i> Previous DTR
                            </button>
                        <?php endif; ?>
                        <?php if($nextDTR): ?>
                            <a href="<?php echo e(route('employee.dtr.show', $nextDTR->id)); ?>" class="btn btn-outline-primary btn-sm">
                                Next DTR <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                                Next DTR <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Period Info and Statistics -->
    <div class="row mb-4 g-3 dtr-summary-grid">
        <div class="col-md-3 dtr-summary-card">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Period</p>
                    <h6 class="mb-3"><?php echo e($dtr->period_start->format('M d')); ?> - <?php echo e($dtr->period_end->format('M d, Y')); ?></h6>
                    <?php if($dtr->status === 'pending_finance_head'): ?>
                        <span class="badge bg-info text-dark p-2">Approved by HR - Awaiting FH Computation</span>
                    <?php else: ?>
                        <span class="badge bg-<?php echo e($dtr->status === 'draft' ? 'warning' : ($dtr->status === 'submitted' ? 'info' : ($dtr->status === 'approved' ? 'success' : 'danger'))); ?> p-2">
                            <?php echo e(ucwords(str_replace('_', ' ', $dtr->status))); ?>

                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 dtr-summary-card">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Hours</p>
                    <h4 class="mb-0"><?php echo e(number_format($stats['total_hours'], 1)); ?> hrs</h4>
                    <small class="text-muted">of <?php echo e($stats['working_days'] * 8); ?> hrs expected</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 dtr-summary-card">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Days Present</p>
                    <h4 class="mb-0"><?php echo e($stats['days_present']); ?>/<?php echo e($stats['working_days']); ?></h4>
                    <small class="text-muted"><?php echo e($stats['days_absent']); ?> day(s) absent</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 dtr-summary-card">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Late</p>
                    <?php if($stats['total_late_minutes'] > 0): ?>
                        <span class="badge bg-danger p-2">Late: <?php echo e($stats['total_late_minutes']); ?> min</span>
                    <?php else: ?>
                        <small class="text-muted">--</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 mb-4 g-3 dtr-extra-summary-grid">
        <div class="col dtr-summary-card">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Absent</p>
                    <h4 class="mb-0 text-danger"><?php echo e($stats['days_absent'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col dtr-summary-card">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Paid Leave</p>
                    <h4 class="mb-0 text-info"><?php echo e($stats['total_paid_leave'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col dtr-summary-card">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Leave Without Pay</p>
                    <h4 class="mb-0 text-warning"><?php echo e($stats['total_leave_without_pay'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col dtr-summary-card">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Holidays</p>
                    <h4 class="mb-0 text-warning"><?php echo e($stats['total_holidays'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col dtr-summary-card">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Suspensions</p>
                    <h4 class="mb-0 text-danger"><?php echo e($stats['total_suspensions'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col dtr-summary-card">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Halfdays</p>
                    <h4 class="mb-0 text-warning"><?php echo e($stats['total_half_days'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col dtr-summary-card">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Suspended Hours</p>
                    <h4 class="mb-0 text-danger"><?php echo e(number_format($stats['total_suspended_hours'] ?? 0, 1)); ?> hrs</h4>
                </div>
            </div>
        </div>
        <div class="col dtr-summary-card">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Early Out</p>
                    <?php if(($stats['total_early_out_minutes'] ?? 0) > 0): ?>
                        <span class="badge bg-warning text-dark p-2">Early Out: <?php echo e($stats['total_early_out_minutes']); ?> min</span>
                    <?php else: ?>
                        <small class="text-muted">--</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <?php if($dtr->status === 'draft'): ?>
        <div class="row mb-4">
            <div class="col-12">
                <form action="<?php echo e(route('employee.dtr.submit', $dtr->id)); ?>" method="POST" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to submit this DTR for approval?')">
                        <i class="fas fa-check"></i> Submit for Approval
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-signature me-2"></i>E-Signature for this DTR</h5>
            <?php if($employeeProfile->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($employeeProfile->signature_path) || \App\Models\FinanceProfile::where('employee_profile_id', $employeeProfile->id)->whereNotNull('signature_path')->exists() || \App\Models\AdminProfile::where('employee_profile_id', $employeeProfile->id)->whereNotNull('signature_path')->exists()): ?>
                <span class="badge bg-success">Saved</span>
            <?php else: ?>
                <span class="badge bg-warning text-dark">Not saved</span>
            <?php endif; ?>
        </div>
        <div class="card-body text-center">
            <?php if($employeeProfile->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($employeeProfile->signature_path) || \App\Models\FinanceProfile::where('employee_profile_id', $employeeProfile->id)->whereNotNull('signature_path')->exists() || \App\Models\AdminProfile::where('employee_profile_id', $employeeProfile->id)->whereNotNull('signature_path')->exists()): ?>
                <div class="border rounded bg-white d-inline-block px-4 py-2">
                    <img src="<?php echo e(route('profile.signature', $employeeProfile->id)); ?>" alt="Employee e-signature" style="width: 280px; max-width: 100%; height: 90px; object-fit: contain;">
                </div>
                <p class="text-muted small mb-0 mt-2">This signature is included in the DTR PDF.</p>
            <?php else: ?>
                <p class="text-muted mb-2">No e-signature saved for this employee.</p>
                <a href="<?php echo e(route('profile')); ?>" class="btn btn-outline-primary btn-sm">Save E-Signature in Profile</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Daily Records Table -->
    <style>
        .dtr-table {
            width: 100%;
            min-width: 980px;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 0 auto;
            font-size: .95rem;
        }
        .dtr-table-scroll {
            max-height: 520px;
            overflow: auto;
        }
        .dtr-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .dtr-table th,
        .dtr-table td {
            border: 1px solid #222;
            min-height: 42px;
            padding: 10px 12px;
            vertical-align: middle;
        }
        .dtr-table th {
            background-color: #fff;
            color: #111;
            font-weight: 700;
            text-transform: uppercase;
        }
        .dtr-table td:first-child {
            font-weight: 600;
        }
        .dtr-table th,
        .dtr-table td,
        .dtr-table td small {
            font-weight: 700;
        }
        .dtr-table tbody td.status-empty {
            background-color: #fff59d !important;
            color: #111 !important;
        }
        .dtr-table tbody td.status-leave,
        .dtr-table.table-hover tbody tr:hover td.status-leave {
            background-color: #92EEFF !important;
            color: #111 !important;
        }
        .dtr-table tbody td.status-absent,
        .dtr-table.table-hover tbody tr:hover td.status-absent {
            background-color: #ff0000 !important;
            color: #111 !important;
        }
        .dtr-table tbody td.status-lwop,
        .dtr-table.table-hover tbody tr:hover td.status-lwop {
            background-color: #ff0000 !important;
            color: #111 !important;
        }
        body.dark-mode .dtr-table tbody td.status-empty,
        body.dark-mode .dtr-table.table-hover tbody tr:hover td.status-empty {
            background-color: #fff59d !important;
            color: #111 !important;
        }
        body.dark-mode .dtr-table tbody td.status-leave,
        body.dark-mode .dtr-table.table-hover tbody tr:hover td.status-leave {
            background-color: #92EEFF !important;
            color: #111 !important;
        }
        body.dark-mode .dtr-table tbody td.status-absent,
        body.dark-mode .dtr-table.table-hover tbody tr:hover td.status-absent {
            background-color: #ff0000 !important;
            color: #111 !important;
        }
        .dtr-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 0.8rem;
            font-weight: 800;
            line-height: 1;
            color: #111;
            border: 2px solid rgba(17, 17, 17, 0.2);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.25);
        }
        .dtr-status-present {
            background-color: #a3e635;
        }
        .dtr-status-late {
            background-color: #facc15;
            color: #111;
        }
        .dtr-status-absent {
            background-color: #ff0000;
            color: #fff;
        }
        .dtr-status-leave {
            background-color: #67e8f9;
        }
        .dtr-status-lwop {
            background-color: #ff0000;
            color: #B4E1EB;
        }
        .dtr-status-weekend {
            background-color: #d1d5db;
            color: #374151;
        }
        .dtr-status-holiday {
            background-color: #fef3c7;
            color: #92400e;
        }
        .dtr-status-suspension {
            background-color: #dc3545;
            color: #ffffff;
        }
        .dtr-status-half-day {
            background-color: #fed7aa;
            color: #9a3412;
        }
        .dtr-table {
            width: 100%;
            min-width: 980px;
            table-layout: fixed;
        }

        .dtr-correction-form {
            width: 100%;
            max-width: 240px;
            margin: 0 auto;
        }

        .dtr-correction-form .form-control,
        .dtr-correction-form .btn {
            width: 100%;
        }

        .dtr-table .dtr-late {
            color: #ff0000;
        }
        body.dark-mode .dtr-table .dtr-late,
        body.dark-mode .dtr-table.table-hover tbody tr:hover td.dtr-late {
            color: #ff0000 !important;
        }
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .dtr-page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .dtr-header-copy {
                width: 100%;
            }

            .dtr-action-group {
                width: 100%;
            }

            .dtr-action-group .d-flex {
                justify-content: flex-start !important;
                width: 100%;
            }

            .dtr-action-group .btn {
                flex: 1 1 calc(50% - 0.5rem);
                min-width: 0;
                white-space: nowrap;
                font-size: 0.72rem;
                padding: 0.55rem 0.5rem;
            }

            .dtr-summary-grid,
            .dtr-extra-summary-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem;
            }

            .dtr-summary-card {
                width: 100%;
                max-width: 100%;
            }

            .dtr-summary-card .card-body {
                padding: 0.9rem 0.7rem;
            }

            .dtr-summary-card h4 {
                font-size: 1.2rem;
            }

            .dtr-summary-card small,
            .dtr-summary-card p {
                font-size: 0.72rem;
            }

            .dtr-table {
                min-width: 620px;
            }
        }

        .dtr-inline-time {
            width: 100%;
            min-width: 112px;
            font-size: 0.8rem;
            margin: 0 auto;
        }

        .dtr-correction-form {
            width: 100%;
            max-width: 240px;
            margin: 0 auto;
        }

        .dtr-correction-form .form-control,
        .dtr-correction-form .btn {
            width: 100%;
        }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daily Time Records</h5>
                </div>
                <div class="card-body">
                    <?php if($daysInPeriod && count($daysInPeriod) > 0): ?>
                        <div class="table-responsive dtr-table-scroll">
                            <table class="table dtr-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 20%;">Date</th>
                                        <th class="text-center" style="width: 18%;">Time-In</th>
                                        <th class="text-center" style="width: 18%;">Time-Out</th>
                                        <th class="text-center" style="width: 16%;">Status</th>
                                        <th class="text-center" style="width: 28%;">Correction</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $leaveStatuses = ['leave', 'leave_paid', 'on leave'];
                                    ?>
                                    <?php $__currentLoopData = $daysInPeriod; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $isDraft = $dtr->status === 'draft';
                                            $logStatus = $day['log'] ? strtolower((string) $day['log']->status) : null;
                                            $isLWOP = $day['status'] === 'lwop';
                                            $isLeave = $day['status'] === 'leave';
                                            $isWeekend = $day['is_weekend'];
                                            $isHoliday = ($day['status'] ?? null) === 'holiday' || ($day['is_holiday'] ?? false);
                                            $isSuspension = ($day['status'] ?? null) === 'suspension' || ($day['is_suspension'] ?? false);
                                            $attendanceStatus = $day['log'] ? $day['log']->getDtrStatus() : null;
                                            $isNoRecordYet = false;
                                            $dayStatus = $day['status'] ?? null;
                                            $isAbsent = $dayStatus === 'absent'
                                                || ($dayStatus === null && !$isWeekend && !$isHoliday && !$isSuspension && !$day['log'] && !$isLeave && !$isLWOP);
                                            $isPending = ($day['status'] ?? null) === 'pending';
                                            $isUpcoming = ($day['status'] ?? null) === 'upcoming';
                                            $isLate = $attendanceStatus && str_contains($attendanceStatus, 'Late');
                                            $isEarlyOutStatus = $attendanceStatus && str_contains($attendanceStatus, 'Early Out');
                                            $isHalfDay = $attendanceStatus === 'Half Day';
                                            $isLateTimeIn = !$isSuspension && ($isLate || (int) ($day['late_minutes'] ?? 0) > 0);
                                            $rowClass = $isSuspension ? 'dtr-status-suspension' : ($isLWOP ? 'dtr-absent' : ($isLeave ? 'dtr-leave' : ($isHoliday && !$day['log'] ? 'dtr-status-holiday' : ($isAbsent ? 'dtr-absent' : ($isHalfDay ? 'dtr-status-half-day' : ($isLate || $isEarlyOutStatus ? 'dtr-late' : ''))))));
                                            $displayStatus = $isSuspension ? 'Suspension' : ($isLWOP ? 'LWOP' : ($isLeave ? 'Leave Paid' : ($isHoliday && !$day['log'] ? 'Holiday' : ($isAbsent ? 'Absent' : ($attendanceStatus ?: ($isWeekend ? 'WKD' : ($isUpcoming ? 'Upcoming' : ($isPending ? 'Pending' : ($isNoRecordYet ? '' : 'Present')))))))));
                                            $isEarlyOut = !$isSuspension && !empty($day['pm_out']) && $day['pm_out'] !== '--' && strtotime($day['pm_out']) < strtotime('17:00');
                                            $legacyApprovedWithoutCorrection = $day['log']
                                                && $day['log']->override_status === 'approved'
                                                && !$day['log']->corrected_time_in
                                                && $day['log']->am_in
                                                && $day['log']->am_in->format('H:i:s') > '07:00:00';
                                            $canRequestCorrection = !$isSuspension && $day['log']
                                                && (in_array($attendanceStatus, ['Late', 'Late / Early Out', 'Early Out', 'Half Day'], true) || $legacyApprovedWithoutCorrection)
                                                && (!in_array($day['log']->override_status, ['pending_branch', 'pending_system_admin', 'approved'], true) || $legacyApprovedWithoutCorrection);
                                            $correctionFormId = 'attendance-correction-' . ($day['log']->id ?? $day['date']->format('Ymd'));
                                        ?>
                                        <tr class="<?php echo e($rowClass); ?>">
                                            <td>
                                                <strong><?php echo e($day['date']->format('M d, Y')); ?></strong><br>
                                                <small class="text-muted"><?php echo e($day['day_name']); ?></small>
                                            </td>
                                            <td class="text-center <?php echo e($isLateTimeIn && !$isLeave && !$isLWOP ? 'dtr-late' : ''); ?>">
                                                <?php if(!$isLeave && !$isLWOP && !$isSuspension && $canRequestCorrection): ?>
                                                    <?php if(in_array($attendanceStatus, ['Late', 'Late / Early Out', 'Half Day'], true) || $legacyApprovedWithoutCorrection): ?>
                                                        <label class="small d-block mb-1">Time-in</label>
                                                        <input form="<?php echo e($correctionFormId); ?>" type="time" name="corrected_time_in" class="form-control form-control-sm mb-1" value="<?php echo e($day['log']->am_in?->format('H:i')); ?>" aria-label="Corrected time-in">
                                                    <?php endif; ?>
                                                    <?php if($attendanceStatus === 'Half Day'): ?>
                                                        <label class="small d-block mb-1">PM time-in</label>
                                                        <input form="<?php echo e($correctionFormId); ?>" type="time" name="corrected_pm_in" class="form-control form-control-sm mb-1" value="<?php echo e($day['log']->pm_in?->format('H:i')); ?>" aria-label="Corrected PM time-in">
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php echo e(!$isLeave && !$isLWOP && !$isSuspension ? ($day['am_in'] ?? '') : '--'); ?>

                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center <?php echo e($isEarlyOut && !$isLeave && !$isLWOP ? 'dtr-late' : ''); ?>">
                                                <?php if(!$isLeave && !$isLWOP && !$isSuspension && $canRequestCorrection && in_array($attendanceStatus, ['Early Out', 'Late / Early Out', 'Half Day'], true)): ?>
                                                    <input form="<?php echo e($correctionFormId); ?>" type="time" name="corrected_time_out" class="form-control form-control-sm mb-1" value="<?php echo e($day['log']->pm_out?->format('H:i')); ?>" aria-label="Corrected time-out">
                                                <?php else: ?>
                                                    <?php echo e(!$isLeave && !$isLWOP && !$isSuspension ? ($day['pm_out'] ?? '') : '--'); ?>

                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center" style="
                                                <?php if($isLWOP): ?>
                                                    background-color: #ff0000; color: #B4E1EB; font-weight: 700;
                                                <?php elseif($isLeave): ?>
                                                    background-color: #92EEFF; color: #111; font-weight: 700;
                                                <?php elseif($isHoliday && !$day['log']): ?>
                                                    background-color: #fef3c7; color: #92400e; font-weight: 700;
                                                <?php elseif($isSuspension): ?>
                                                    background-color: #dc3545; color: #ffffff; font-weight: 700;
                                                <?php elseif($isHalfDay): ?>
                                                    background-color: #fed7aa; color: #9a3412; font-weight: 700;
                                                <?php elseif($isAbsent): ?>
                                                    background-color: #ff0000; color: #111; font-weight: 700;
                                                <?php elseif($isWeekend): ?>
                                                    background-color: #d1d5db; color: #374151; font-weight: 700;
                                                <?php elseif($isPending): ?>
                                                    background-color: #e5e7eb; color: #4b5563; font-weight: 700;
                                                <?php elseif($isNoRecordYet): ?>
                                                    background-color: transparent; color: transparent; font-weight: 700;
                                                <?php else: ?>
                                                    background-color: #005F02; color: #ffffff; font-weight: 700;
                                                <?php endif; ?>
                                            ">
                                                <?php if($isLWOP): ?>
                                                    LWOP
                                                <?php elseif($isLeave): ?>
                                                    Leave Paid
                                                <?php elseif($isSuspension): ?>
                                                    Suspension
                                                <?php elseif($isHoliday && !$day['log']): ?>
                                                    Holiday
                                                <?php elseif($isHalfDay): ?>
                                                    Half Day
                                                <?php elseif($isAbsent): ?>
                                                    Absent
                                                <?php elseif($isLate): ?>
                                                    <?php echo e($attendanceStatus); ?>

                                                <?php elseif($isEarlyOutStatus): ?>
                                                    Early Out
                                                <?php elseif($isWeekend): ?>
                                                    WKD
                                                <?php elseif($isNoRecordYet): ?>
                                                <?php else: ?>
                                                    Present
                                                <?php endif; ?>
                                                <?php if($day['log'] && in_array($day['log']->override_status, ['pending_branch', 'pending_system_admin'], true)): ?>
                                                    <small class="d-block text-muted mt-1">Adjustment pending approval</small>
                                                <?php elseif($day['log'] && $day['log']->override_status === 'approved'): ?>
                                                    <small class="d-block text-success mt-1">Adjusted to Present</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($canRequestCorrection): ?>
                                                    <form id="<?php echo e($correctionFormId); ?>" method="POST" action="<?php echo e(route('employee.dtr.attendance.adjust-present', $day['log']->id)); ?>" class="dtr-correction-form">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="text" name="reason" class="form-control form-control-sm mb-1" placeholder="Reason" required minlength="5">
                                                        <button type="submit" class="btn btn-sm btn-outline-dark">Request Attendance Correction</button>
                                                    </form>
                                                <?php elseif($day['log'] && in_array($day['log']->override_status, ['pending_branch', 'pending_system_admin'], true)): ?>
                                                    <small class="d-block text-muted">Adjustment pending approval</small>
                                                <?php elseif($day['log'] && $day['log']->override_status === 'approved'): ?>
                                                    <small class="d-block text-success">Adjusted to Present</small>
                                                <?php else: ?>
                                                    --
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No attendance records for this period.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.dtr-inline-time').forEach((input) => {
            input.addEventListener('change', () => {
                input.form?.querySelector('[name="reason"]')?.focus();
            });
        });
    </script>

    <!-- Remarks Section -->
    <?php if($dtr->remarks): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Remarks</h6>
                    </div>
                    <div class="card-body">
                        <?php echo e($dtr->remarks); ?>

                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views/employee/dtr/show.blade.php ENDPATH**/ ?>