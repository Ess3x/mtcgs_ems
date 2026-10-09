<?php $__env->startSection('title', 'Admin Dashboard'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .dashboard-shell { min-width: 0; overflow-x: hidden; }
    .dashboard-shell .dashboard-stat-card .card-body > .d-flex > div:first-child { min-width: 0; }
    .dashboard-shell .dashboard-stat-card .text-uppercase { overflow-wrap: anywhere; }
    .dashboard-shell .chart-container { min-width: 0; }

    @media (max-width: 576px) {
        .dashboard-shell .dashboard-hero .card-body { padding: 1.15rem; }
        .dashboard-shell .dashboard-hero .d-flex { align-items: flex-start !important; }
        .dashboard-shell .welcome-title { line-height: 1.25; }
        .dashboard-shell .welcome-date { font-size: 1.65rem; }
        .dashboard-shell .dashboard-stat-card .card-body { padding: 0.85rem; }
        .dashboard-shell .dashboard-stat-card .d-flex { gap: 0.5rem !important; }
        .dashboard-shell .dashboard-stat-card .stat-icon { width: 38px; height: 38px; border-radius: 10px; }
        .dashboard-shell .dashboard-stat-card .stat-icon i { font-size: 1rem; }
        .dashboard-shell .dashboard-stat-card small { display: block; line-height: 1.2; }
        .dashboard-shell .chart-container[style*="height: 320px"] { height: 250px !important; }
        .dashboard-shell .chart-container[style*="height: 220px"] { height: 200px !important; }
        .dashboard-shell .card-header { padding: 0.85rem 1rem; }
        .dashboard-shell .card-footer { gap: 0.5rem; }
        .dashboard-shell .card-footer > div { min-width: 0; flex: 1 1 0; }
        .dashboard-shell .card-footer .badge { white-space: normal; }
        .dashboard-shell .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .dashboard-shell .table { min-width: 520px; }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid dashboard-shell p-0">
    <div id="dashboardNotificationContainer"></div>
    <?php echo $__env->make('dashboard.birthday-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Welcome Hero -->
    <div class="card dashboard-hero mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 fw-bold welcome-title">Welcome back, <?php echo e(Auth::user()->name); ?>!</h2>
                    <p class="mt-2 mb-0 opacity-75">
                        <i class="fas fa-building me-1"></i> Tracking attendance for
                        <strong><?php echo e($branchName ?? 'All Branches'); ?></strong>.
                    </p>
                </div>
                <div class="text-end">
                    <div class="display-5 fw-bold mb-0 welcome-date"><?php echo e(now()->format('M d')); ?></div>
                    <div class="opacity-75"><?php echo e(now()->format('l')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Total Employees</div>
                            <div class="fw-bold stat-value" style="font-size: 1.75rem;"><?php echo e($totalEmployees ?? 0); ?></div>
                            <small class="text-muted">Active workforce</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(102,126,234,0.12); color: #667eea;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Present Today</div>
                            <div class="fw-bold text-success stat-value" style="font-size: 1.75rem;"><?php echo e($presentToday ?? 0); ?></div>
                            <small class="text-muted"><?php echo e($attendanceRate ?? 0); ?>% rate</small>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: <?php echo e($attendanceRate ?? 0); ?>%"></div>
                            </div>
                        </div>
                        <div class="stat-icon" style="background: rgba(40,167,69,0.12); color: #28a745;">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Pending Leaves</div>
                            <div class="fw-bold text-warning stat-value" style="font-size: 1.75rem;"><?php echo e($pendingLeaves ?? 0); ?></div>
                            <a href="<?php echo e(route('leave.index')); ?>" class="text-decoration-none small fw-semibold">
                                Review <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="stat-icon" style="background: rgba(255,193,7,0.15); color: #f59e0b;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Verifications</div>
                            <div class="fw-bold text-danger stat-value" style="font-size: 1.75rem;"><?php echo e($pendingCount ?? 0); ?></div>
                            <a href="<?php echo e(route('admin.verifications')); ?>" class="text-decoration-none small fw-semibold">
                                Verify <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="stat-icon" style="background: rgba(220,53,69,0.12); color: #dc3545;">
                            <i class="fas fa-id-card"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-chart-line text-primary"></i>
                    <span>Attendance Trend (7 days)</span>
                </div>
                <div class="card-body chart-container" style="position: relative; height: 320px;">
                    <canvas id="attendanceTrendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-chart-pie text-primary"></i>
                    <span>Today's Distribution</span>
                </div>
                <div class="card-body chart-container" style="position: relative; height: 220px;">
                    <canvas id="attendancePieChart"></canvas>
                </div>
                <div class="card-footer bg-transparent d-flex justify-content-around text-center py-3">
                    <div>
                        <span class="badge bg-success w-100 mb-1">Present</span>
                        <div class="fw-bold mt-1"><?php echo e($presentToday ?? 0); ?></div>
                    </div>
                    <div>
                        <span class="badge bg-warning text-dark w-100 mb-1">Late</span>
                        <div class="fw-bold mt-1"><?php echo e($lateToday ?? 0); ?></div>
                    </div>
                    <div>
                        <span class="badge bg-danger w-100 mb-1">Absent</span>
                        <div class="fw-bold mt-1"><?php echo e($absentToday ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-clock text-primary"></i>
                <span>Recent Attendance</span>
            </div>
            <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
                <i class="fas fa-sync-alt"></i> Refresh 10s
            </span>
        </div>
        <div class="table-responsive" style="border: 0; border-radius: 0 0 14px 14px;">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th class="text-center table-hide-mobile">Time-In</th>
                        <th class="text-center table-hide-mobile">Time-Out</th>
                        <th class="text-center">Status</th>
                        <th class="text-center table-hide-mobile">Source</th>
                        <th class="text-center">Late/OT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $recentAttendance ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="fw-medium"><?php echo e($att['date']); ?></span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar bg-primary">
                                    <?php echo e(substr($att['employee'], 0, 1)); ?>

                                </div>
                                <div class="d-none d-md-block">
                                    <div class="fw-semibold"><?php echo e($att['employee']); ?></div>
                                    <small class="text-muted"><?php echo e($att['employee_number']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center table-hide-mobile">
                            <?php if($att['time_in'] != '--'): ?> <span class="badge bg-success"><?php echo e($att['time_in']); ?></span>
                            <?php else: ?> <span class="text-muted">--</span> <?php endif; ?>
                        </td>
                        <td class="text-center table-hide-mobile">
                            <?php if($att['time_out'] != '--'): ?> <span class="badge bg-primary"><?php echo e($att['time_out']); ?></span>
                            <?php else: ?> <span class="text-muted">--</span> <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if($att['status'] == 'present'): ?> <span class="badge bg-success">Present</span>
                            <?php elseif($att['status'] == 'late'): ?> <span class="badge bg-warning text-dark">Late</span>
                            <?php else: ?> <span class="badge bg-danger">Absent</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center table-hide-mobile">
                            <?php if($att['verification_method'] === 'fingerprint'): ?>
                                <span class="badge bg-info text-dark">FP</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo e(substr(ucfirst($att['verification_method'] ?? 'manual'), 0, 1)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php $late = (int)($att['late_minutes'] ?? 0); $ot = (float)($att['overtime_hours'] ?? 0); ?>
                            <?php if($late > 0 || $ot > 0): ?>
                                <?php if($late > 0): ?><small class="text-warning fw-semibold d-block"><?php echo e($late); ?>m</small><?php endif; ?>
                                <?php if($ot > 0): ?><span class="badge bg-warning text-dark"><?php echo e($ot); ?>h</span><?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center py-5">
                        <i class="fas fa-calendar-alt fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">No attendance records found</p>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        const isMobile = window.innerWidth <= 768;
        const isSmallMobile = window.innerWidth <= 480;

        const trendEl = document.getElementById('attendanceTrendChart');
        if (trendEl) {
            new Chart(trendEl.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($trendLabels ?? [], 15, 512) ?>,
                    datasets: [
                        {
                            label: 'Present',
                            data: <?php echo json_encode($trendPresent ?? [], 15, 512) ?>,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40,167,69,0.08)',
                            tension: 0.4, fill: true,
                            pointRadius: isMobile ? 2 : 4,
                            pointHoverRadius: isMobile ? 4 : 6,
                            borderWidth: isSmallMobile ? 1.5 : 2
                        },
                        {
                            label: 'Late',
                            data: <?php echo json_encode($trendLate ?? [], 15, 512) ?>,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.08)',
                            tension: 0.4, fill: true,
                            pointRadius: isMobile ? 2 : 4,
                            pointHoverRadius: isMobile ? 4 : 6,
                            borderWidth: isSmallMobile ? 1.5 : 2
                        },
                        {
                            label: 'Absent',
                            data: <?php echo json_encode($trendAbsent ?? [], 15, 512) ?>,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220,53,69,0.08)',
                            tension: 0.4, fill: true,
                            pointRadius: isMobile ? 2 : 4,
                            pointHoverRadius: isMobile ? 4 : 6,
                            borderWidth: isSmallMobile ? 1.5 : 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: {
                            position: isMobile ? 'bottom' : 'top',
                            labels: {
                                font: { size: isSmallMobile ? 10 : isMobile ? 11 : 12 },
                                padding: isMobile ? 8 : 15,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            titleFont: { size: isSmallMobile ? 11 : 12 },
                            bodyFont: { size: isSmallMobile ? 10 : 11 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { display: !isSmallMobile, drawBorder: false },
                            ticks: { font: { size: isSmallMobile ? 9 : 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: isSmallMobile ? 9 : 11 } }
                        }
                    }
                }
            });
        }

        const pieEl = document.getElementById('attendancePieChart');
        if (pieEl) {
            const presentToday = <?php echo e((int)($presentToday ?? 0)); ?>;
            const lateToday = <?php echo e((int)($lateToday ?? 0)); ?>;
            const absentToday = <?php echo e((int)($absentToday ?? 0)); ?>;
            new Chart(pieEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Late', 'Absent'],
                    datasets: [{
                        data: [presentToday, lateToday, absentToday],
                        backgroundColor: ['#28a745', '#f59e0b', '#dc3545'],
                        borderWidth: isSmallMobile ? 0.5 : 2,
                        hoverOffset: isMobile ? 5 : 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    cutout: isMobile ? '50%' : '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: isSmallMobile ? 10 : isMobile ? 11 : 12 },
                                padding: isMobile ? 8 : 15,
                                usePointStyle: true,
                                boxWidth: isMobile ? 8 : 12
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs_ems\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>