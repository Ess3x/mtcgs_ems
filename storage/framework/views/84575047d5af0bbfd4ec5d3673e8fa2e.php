<?php $__env->startSection('title', 'Branch Admin Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid dashboard-shell p-0">
    <!-- Header Hero -->
    <div class="card dashboard-hero mb-4" style="background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%);">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 fw-bold welcome-title">Welcome, Branch Administrator!</h2>
                    <p class="mt-2 mb-0 opacity-90">
                        Managing: <strong><?php echo e($branchName ?? 'N/A'); ?></strong> Branch
                    </p>
                </div>
                <div class="text-end">
                    <div class="display-6 fw-bold mb-0"><?php echo e(now()->format('M d')); ?></div>
                    <div class="opacity-90"><?php echo e(now()->format('l')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Total Employees</div>
                            <div class="fw-bold" style="font-size: 1.75rem;"><?php echo e($totalEmployees ?? 0); ?></div>
                            <small class="text-muted">Active workforce</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(79,70,229,0.12); color: #4f46e5;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Present Today</div>
                            <div class="fw-bold text-success" style="font-size: 1.75rem;"><?php echo e($presentToday ?? 0); ?></div>
                            <small class="text-muted">Across all roles</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(40,167,69,0.12); color: #28a745;">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Pending Leaves</div>
                            <div class="fw-bold text-warning" style="font-size: 1.75rem;"><?php echo e($pendingLeaves ?? 0); ?></div>
                            <small class="text-muted">Awaiting review</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fas fa-clock text-primary"></i>
            <span>Recent Attendance (<?php echo e($branchName ?? ''); ?> Branch)</span>
        </div>
        <div class="table-responsive" style="border: 0; border-radius: 0 0 14px 14px;">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>AM In</th>
                        <th>AM Out</th>
                        <th>PM In</th>
                        <th>PM Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $recentAttendance ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($att['date']); ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar bg-primary"><?php echo e(substr($att['employee'], 0, 1)); ?></div>
                                <div>
                                    <div class="fw-semibold"><?php echo e($att['employee']); ?></div>
                                    <small class="text-muted"><?php echo e($att['employee_number']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo e($att['am_in']); ?></td>
                        <td><?php echo e($att['am_out']); ?></td>
                        <td><?php echo e($att['pm_in']); ?></td>
                        <td><?php echo e($att['pm_out']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($att['status'] == 'present' ? 'success' : ($att['status'] == 'late' ? 'warning text-dark' : 'secondary')); ?>">
                                <?php echo e(ucfirst($att['status'])); ?>

                            </span>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center py-5">
                        <i class="fas fa-calendar-day fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">No records</p>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\branch-dashboard.blade.php ENDPATH**/ ?>