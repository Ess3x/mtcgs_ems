<?php $__env->startSection('title', 'Employee Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid dashboard-shell p-0">
    <!-- Header Hero -->
    <div class="card dashboard-hero mb-4" style="background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 fw-bold welcome-title">Welcome, <?php echo e($profile->first_name); ?> <?php echo e($profile->last_name); ?>!</h2>
                    <p class="mt-2 mb-0 opacity-90">
                        <i class="fas fa-building me-1"></i> <?php echo e(optional($profile->branch)->branch_name ?? 'N/A'); ?> Branch
                        <span class="ms-3"><i class="fas fa-id-card me-1"></i> <?php echo e($profile->employee_number); ?></span>
                    </p>
                </div>
                <div class="text-end">
                    <div class="display-6 fw-bold mb-0"><?php echo e(now()->format('M d')); ?></div>
                    <div class="opacity-90"><?php echo e(now()->format('l')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto mb-2" style="background: rgba(255,193,7,0.15); color: #f59e0b;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="text-muted text-uppercase small fw-semibold mb-1">Pending Leave Requests</div>
                    <div class="fw-bold" style="font-size: 1.75rem;"><?php echo e($pendingLeaves ?? 0); ?></div>
                    <small class="text-muted">Waiting for approval</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto mb-2" style="background: rgba(40,167,69,0.12); color: #28a745;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="text-muted text-uppercase small fw-semibold mb-1">Today's Status</div>
                    <div class="fw-bold" style="font-size: 1.4rem;">
                        <?php if($todayAttendance): ?> <?php echo e(ucfirst($todayAttendance->status ?? 'N/A')); ?>

                        <?php else: ?> Not logged
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">Attendance tracking</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto mb-2" style="background: rgba(79,70,229,0.12); color: #4f46e5;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="text-muted text-uppercase small fw-semibold mb-1">Branch</div>
                    <div class="fw-bold" style="font-size: 1.4rem;"><?php echo e(optional($profile->branch)->branch_name ?? 'N/A'); ?></div>
                    <small class="text-muted">Your assigned branch</small>
                </div>
            </div>
        </div>
    </div>

    <!-- DTR Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span class="d-flex align-items-center gap-2">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <span>Daily Time Record (DTR)</span>
                    </span>
                    <a href="<?php echo e(route('employee.dtr.index')); ?>" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Current DTR Period</p>
                    <?php
                        $today = now();
                        if ($today->day <= 15) {
                            $period_start = $today->copy()->startOfMonth();
                            $period_end = $today->copy()->setDay(15);
                        } else {
                            $period_start = $today->copy()->setDay(16);
                            $period_end = $today->copy()->endOfMonth();
                        }
                    ?>
                    <h5 class="fw-bold mb-4"><?php echo e($period_start->format('M d')); ?> - <?php echo e($period_end->format('M d, Y')); ?></h5>
                    <a href="<?php echo e(route('employee.dtr.index')); ?>" class="btn btn-outline-primary w-100">
                        <i class="fas fa-eye me-2"></i> View DTR Details & Submit
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <a href="<?php echo e(route('employee.schedule')); ?>" class="card h-100 text-decoration-none">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span class="d-flex align-items-center gap-2">
                        <i class="fas fa-clock text-info"></i>
                        <span>My Schedule</span>
                    </span>
                    <i class="fas fa-arrow-right text-muted"></i>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <?php if($profile->shift): ?>
                        <h5 class="fw-bold text-dark mb-2"><?php echo e($profile->shift->name); ?></h5>
                        <p class="text-muted mb-1">
                            <?php echo e(\Carbon\Carbon::parse($profile->shift->start_time)->format('h:i A')); ?> -
                            <?php echo e(\Carbon\Carbon::parse($profile->shift->end_time)->format('h:i A')); ?>

                        </p>
                        <small class="text-muted">Click to view full schedule</small>
                    <?php else: ?>
                        <h5 class="fw-bold text-dark mb-2">No schedule assigned</h5>
                        <small class="text-muted">Click to view schedule details</small>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-white" style="background: linear-gradient(135deg, #4f46e5, #6366f1); border: 0;">
                <div class="card-body text-center py-4">
                    <i class="fas fa-calendar-check fa-2x mb-2 opacity-90"></i>
                    <div class="text-uppercase small fw-semibold opacity-90">Days Present</div>
                    <div class="fw-bold" style="font-size: 2rem;"><?php echo e($stats['days_present'] ?? 0); ?></div>
                    <small class="opacity-90">This month</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white dashboard-days-late-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-clock fa-2x mb-2 opacity-90"></i>
                    <div class="text-uppercase small fw-semibold opacity-90">Days Late</div>
                    <div class="fw-bold" style="font-size: 2rem;"><?php echo e($stats['days_late'] ?? 0); ?></div>
                    <small class="opacity-90">This month</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white" style="background: linear-gradient(135deg, #06b6d4, #0ea5e9); border: 0;">
                <div class="card-body text-center py-4">
                    <i class="fas fa-fingerprint fa-2x mb-2 opacity-90"></i>
                    <div class="text-uppercase small fw-semibold opacity-90">Biometric Status</div>
                    <div class="fw-bold" style="font-size: 2rem;"><?php echo e($hasFingerprint ? '✓' : '✗'); ?></div>
                    <small class="opacity-90"><?php echo e($hasFingerprint ? 'Registered' : 'Not Registered'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance History -->
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fas fa-history text-primary"></i>
            <span>Today's Attendance</span>
        </div>
        <div class="table-responsive" style="border: 0; border-radius: 0 0 14px 14px;">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time-in</th>
                        <th>Time-out</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Late</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $recentAttendance ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e(optional($att->attendance_date)->format('M d, Y') ?? '--'); ?></td>
                        <td><?php echo e(optional($att->am_in)->format('h:i A') ?? '--'); ?></td>
                        <td><?php echo e(optional($att->pm_out)->format('h:i A') ?? '--'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($att['status'] == 'present' ? 'success' : ($att['status'] == 'late' ? 'warning text-dark' : 'secondary')); ?>">
                                <?php echo e(ucfirst($att['status'])); ?>

                            </span>
                        </td>
                        <td>
                            <?php if($att['verification_method'] === 'fingerprint'): ?>
                                <span class="badge bg-info text-dark">Fingerprint</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo e(ucfirst($att['verification_method'] ?? 'manual')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($att['late_minutes'] > 0): ?>
                                <span class="text-danger fw-semibold"><?php echo e($att['late_minutes']); ?> min</span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="text-center py-5">
                        <i class="fas fa-calendar-day fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">No attendance records</p>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\employee\dashboard.blade.php ENDPATH**/ ?>