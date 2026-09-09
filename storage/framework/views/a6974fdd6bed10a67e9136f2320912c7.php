<?php $__env->startSection('title', 'Finance Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $financeProfile = Auth::user()->getFinanceProfile();
    $currentEmployeeProfile = $employeeProfile ?? ($financeProfile ? App\Models\EmployeeProfile::find($financeProfile->employee_profile_id) : null);
    $myAttendance = $currentEmployeeProfile ? App\Models\AttendanceLog::where('employee_profile_id', $currentEmployeeProfile->id)->whereDate('attendance_date', today())->first() : null;
?>

<div class="container-fluid dashboard-shell p-0">
    <div id="dashboardNotificationContainer"></div>

    <!-- Header Hero -->
    <div class="card dashboard-hero mb-4" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 fw-bold welcome-title"><i class="fas fa-chart-line me-2"></i>Finance Dashboard</h2>
                    <p class="mt-2 mb-0 opacity-90">
                        <i class="fas fa-building me-1"></i> <?php echo e($branchName ?? 'N/A'); ?> Branch
                    </p>
                </div>
                <div class="text-end">
                    <div class="display-6 fw-bold mb-0"><?php echo e(now()->format('M d')); ?></div>
                    <div class="opacity-90"><?php echo e(now()->format('l, F j, Y')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fas fa-bolt text-warning"></i>
            <span>Quick Actions</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="<?php echo e(route('finance.employees')); ?>" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-2">
                        <i class="fas fa-users fa-2x"></i>
                        <span>View Employees</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo e(route('admin.payroll.periods')); ?>" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center gap-2">
                        <i class="fas fa-calculator fa-2x"></i>
                        <span>Process Payroll</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo e(route('leave.index')); ?>" class="btn btn-outline-warning w-100 py-3 d-flex flex-column align-items-center gap-2">
                        <i class="fas fa-calendar-alt fa-2x"></i>
                        <span>Verify Leaves</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Total Employees</div>
                            <div class="fw-bold" style="font-size: 1.75rem;"><?php echo e($stats['total_employees'] ?? 0); ?></div>
                        </div>
                        <div class="stat-icon" style="background: rgba(79,70,229,0.12); color: #4f46e5;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Present Today</div>
                            <div class="fw-bold text-success" style="font-size: 1.75rem;"><?php echo e($stats['total_present_today'] ?? 0); ?></div>
                        </div>
                        <div class="stat-icon" style="background: rgba(40,167,69,0.12); color: #28a745;">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Late Today</div>
                            <div class="fw-bold text-warning" style="font-size: 1.75rem;"><?php echo e($stats['total_late_today'] ?? 0); ?></div>
                        </div>
                        <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Pending Leaves</div>
                            <div class="fw-bold text-danger" style="font-size: 1.75rem;"><?php echo e($stats['pending_leaves'] ?? 0); ?></div>
                        </div>
                        <div class="stat-icon" style="background: rgba(220,53,69,0.12); color: #dc3545;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Summary + My DTR Row -->
    <div class="row g-3 mb-4">
        <?php if(Auth::user()->role !== 'finance_head'): ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="d-flex align-items-center gap-2">
                        <i class="fas fa-money-bill-wave text-primary"></i>
                        <span>Payroll Summary</span>
                    </span>
                    <span class="badge bg-info"><?php echo e($branchName ?? 'Branch'); ?></span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="bg-light rounded-3 p-3">
                                <div class="text-muted small mb-1">Monthly Payroll Total</div>
                                <div class="fw-bold" style="font-size: 1.6rem;">₱<?php echo e(number_format($stats['monthly_payroll_total'] ?? 0, 2)); ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-light rounded-3 p-3">
                                <div class="text-muted small mb-1">Last Payroll</div>
                                <div class="fw-bold" style="font-size: 1.6rem;">₱<?php echo e(number_format($lastPayroll->net_pay ?? 0, 2)); ?></div>
                                <small class="text-muted"><?php echo e(optional($lastPayroll)->created_at ? optional($lastPayroll)->created_at->format('M d, Y') : 'No payroll yet'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="d-flex align-items-center gap-2">
                        <i class="fas fa-user-clock text-primary"></i>
                        <span>My Daily Time Record</span>
                    </span>
                    <span class="badge bg-secondary">Personal</span>
                </div>
                <div class="card-body">
                    <?php if(!$financeProfile || !$financeProfile->is_fingerprint_registered): ?>
                        <div class="alert alert-warning mb-3 d-flex align-items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Please register your fingerprint first.</span>
                        </div>
                        <button onclick="registerFinanceFingerprint()" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-fingerprint me-2"></i> Register Fingerprint
                        </button>
                    <?php else: ?>
                        <div class="bg-light rounded-3 p-3 mb-3">
                            <div class="text-muted small">Employee</div>
                            <div class="fw-semibold"><?php echo e(optional($currentEmployeeProfile)->first_name ?? 'N/A'); ?> <?php echo e(optional($currentEmployeeProfile)->last_name ?? ''); ?></div>
                            <small class="text-muted"><?php echo e(optional($currentEmployeeProfile)->employee_number ?? 'No employee profile linked'); ?></small>
                        </div>
                        <?php if(!$myAttendance || !$myAttendance->am_in || !$myAttendance->am_out || !$myAttendance->pm_in || !$myAttendance->pm_out): ?>
                            <?php
                                $financeAttendanceButtonClass = 'success';
                                if ($myAttendance && $myAttendance->am_in && !$myAttendance->am_out) {
                                    $financeAttendanceButtonClass = 'warning';
                                } elseif ($myAttendance && $myAttendance->am_out && !$myAttendance->pm_in) {
                                    $financeAttendanceButtonClass = 'info';
                                } elseif ($myAttendance && $myAttendance->pm_in && !$myAttendance->pm_out) {
                                    $financeAttendanceButtonClass = 'danger';
                                }
                            ?>

                            <?php if($myAttendance && $myAttendance->am_in && !$myAttendance->am_out): ?>
                                <div class="bg-light rounded-3 p-3 mb-3">
                                    <div class="text-muted small">AM In</div>
                                    <div class="fw-semibold"><?php echo e(date('h:i A', strtotime($myAttendance->am_in))); ?></div>
                                    <?php if($myAttendance->late_minutes > 0): ?>
                                        <small class="text-danger">Late by <?php echo e($myAttendance->late_minutes); ?> mins</small>
                                    <?php endif; ?>
                                </div>
                            <?php elseif($myAttendance && $myAttendance->am_out && !$myAttendance->pm_in): ?>
                                <div class="bg-light rounded-3 p-3 mb-3">
                                    <div class="text-muted small">AM Out</div>
                                    <div class="fw-semibold"><?php echo e(date('h:i A', strtotime($myAttendance->am_out))); ?></div>
                                </div>
                            <?php elseif($myAttendance && $myAttendance->pm_in && !$myAttendance->pm_out): ?>
                                <div class="bg-light rounded-3 p-3 mb-3">
                                    <div class="text-muted small">PM In</div>
                                    <div class="fw-semibold"><?php echo e(date('h:i A', strtotime($myAttendance->pm_in))); ?></div>
                                </div>
                            <?php elseif(!$myAttendance || !$myAttendance->am_in): ?>
                                <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                                    <i class="fas fa-clock"></i>
                                    <span>No time record yet for today.</span>
                                </div>
                            <?php endif; ?>

                            <button onclick="processFinanceAttendance()" class="btn btn-<?php echo e($financeAttendanceButtonClass); ?> btn-lg px-4">
                                <i class="fas fa-fingerprint me-2"></i> FINGERPRINT ATTENDANCE
                            </button>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> Completed for Today
                                <div class="mt-2 small">
                                    AM In: <?php echo e(date('h:i A', strtotime($myAttendance->am_in))); ?> · AM Out: <?php echo e(date('h:i A', strtotime($myAttendance->am_out))); ?><br>
                                    PM In: <?php echo e(date('h:i A', strtotime($myAttendance->pm_in))); ?> · PM Out: <?php echo e(date('h:i A', strtotime($myAttendance->pm_out))); ?>

                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- My DTR + Biometric Registration Row -->
    <?php if(Auth::user()->role !== 'finance_head'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-fingerprint text-primary"></i>
                    <span>Today's Time Record</span>
                </div>
                <div class="card-body text-center py-4">
                    <?php if(!$financeProfile || !$financeProfile->is_fingerprint_registered): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Please register your fingerprint first.
                        </div>
                        <button onclick="registerFinanceFingerprint()" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-fingerprint"></i> Register My Fingerprint
                        </button>
                    <?php else: ?>
                        <?php if($myAttendance): ?>
                            <div class="alert alert-success d-inline-block">
                                <i class="fas fa-check-circle"></i> Completed for Today
                                <div class="mt-2 small">
                                    AM In: <?php echo e(date('h:i A', strtotime($myAttendance->am_in))); ?> · AM Out: <?php echo e(date('h:i A', strtotime($myAttendance->am_out))); ?><br>
                                    PM In: <?php echo e(date('h:i A', strtotime($myAttendance->pm_in))); ?> · PM Out: <?php echo e(date('h:i A', strtotime($myAttendance->pm_out))); ?>

                                </div>
                            </div>
                            <?php if($myAttendance->overtime_hours > 0): ?>
                                <div class="mt-2"><span class="badge bg-warning text-dark">Overtime: <?php echo e($myAttendance->overtime_hours); ?> hrs</span></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info d-inline-block">
                                <i class="fas fa-clock"></i> No time record yet for today.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-fingerprint text-success"></i>
                    <span>Employee Biometric Registration</span>
                </div>
                <div class="card-body" id="employeeBiometricList">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2 text-muted">Loading employees...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Today's Attendance Table -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="d-flex align-items-center gap-2">
                <i class="fas fa-clock text-info"></i>
                <span>Today's Attendance</span>
            </span>
            <span class="badge bg-info text-dark">Updated <?php echo e(now()->format('h:i A')); ?></span>
        </div>
        <div class="table-responsive" style="border: 0; border-radius: 0 0 14px 14px;">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th class="text-center table-hide-mobile">AM In</th>
                        <th class="text-center table-hide-mobile">AM Out</th>
                        <th class="text-center table-hide-mobile">PM In</th>
                        <th class="text-center table-hide-mobile">PM Out</th>
                        <th class="text-center">Status</th>
                        <th class="text-center table-hide-mobile">Late</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $todayAttendance ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar bg-primary">
                                    <?php echo e(substr(optional($att->employeeProfile)->first_name ?? 'N', 0, 1)); ?>

                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo e(optional($att->employeeProfile)->first_name ?? ''); ?> <?php echo e(optional($att->employeeProfile)->last_name ?? ''); ?></div>
                                    <small class="text-muted"><?php echo e(optional($att->employeeProfile)->employee_number ?? ''); ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center table-hide-mobile"><?php echo e($att->am_in ? date('h:i A', strtotime($att->am_in)) : '--'); ?></td>
                        <td class="text-center table-hide-mobile"><?php echo e($att->am_out ? date('h:i A', strtotime($att->am_out)) : '--'); ?></td>
                        <td class="text-center table-hide-mobile"><?php echo e($att->pm_in ? date('h:i A', strtotime($att->pm_in)) : '--'); ?></td>
                        <td class="text-center table-hide-mobile"><?php echo e($att->pm_out ? date('h:i A', strtotime($att->pm_out)) : '--'); ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?php echo e($att->status === 'present' ? 'success' : ($att->status === 'late' ? 'warning text-dark' : 'secondary')); ?>">
                                <?php echo e(ucfirst($att->status)); ?>

                            </span>
                        </td>
                        <td class="text-center table-hide-mobile">
                            <?php echo e($att->late_minutes > 0 ? $att->late_minutes . ' min' : '-'); ?>

                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center py-5">
                        <i class="fas fa-calendar-day fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">No attendance records for today</p>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reports and Payroll Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-file-alt text-primary"></i>
                    <span>Generate Reports</span>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="<?php echo e(route('reports.monthly', ['year' => date('Y'), 'month' => date('m')])); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-file-alt me-2"></i> Monthly Payroll Report
                    </a>
                    <a href="<?php echo e(route('reports.contributions', ['year' => date('Y'), 'month' => date('m')])); ?>" class="btn btn-outline-success">
                        <i class="fas fa-chart-line me-2"></i> Contributions Report
                    </a>
                    <a href="<?php echo e(route('reports.tax', ['year' => date('Y'), 'month' => date('m')])); ?>" class="btn btn-outline-warning">
                        <i class="fas fa-file-invoice me-2"></i> BIR Tax Report
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-chart-bar text-success"></i>
                    <span>Payroll Overview</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                        <i class="fas fa-money-bill"></i>
                        <div>
                            <div class="small">Monthly Payroll Total</div>
                            <div class="fw-bold" style="font-size: 1.4rem;">₱<?php echo e(number_format($stats['monthly_payroll_total'] ?? 0, 2)); ?></div>
                        </div>
                    </div>
                    <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0">
                        <i class="fas fa-history"></i>
                        <div>
                            <div class="small">Last Payroll Net</div>
                            <div class="fw-bold" style="font-size: 1.4rem;">₱<?php echo e(number_format($lastPayroll->net_pay ?? 0, 2)); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function showDashboardNotification(type, message) {
    const container = document.getElementById('dashboardNotificationContainer');
    if (!container) return;

    const icon = type === 'success'
        ? '<i class="fas fa-check-circle me-2"></i>'
        : type === 'warning'
            ? '<i class="fas fa-exclamation-circle me-2"></i>'
            : '<i class="fas fa-times-circle me-2"></i>';
    const alertClass = type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' : 'alert-danger';

    container.innerHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show shadow-sm" role="alert">
            <div class="d-flex align-items-center">
                ${icon}
                <div>${message}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    setTimeout(() => {
        const alertEl = container.querySelector('.alert');
        if (alertEl) alertEl.remove();
    }, 4500);
}

function getOrCreateFingerprint(key, seed) {
    const stored = localStorage.getItem(key);
    if (stored) {
        return stored;
    }

    const generated = btoa(`${seed}_${Date.now()}`);
    localStorage.setItem(key, generated);
    return generated;
}

async function registerFinanceFingerprint() {
    const fakeFingerprint = getOrCreateFingerprint('mtcgs_finance_fingerprint', 'finance_fingerprint');
    try {
        const response = await fetch('/api/biometric/register-finance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ fingerprint_data: fakeFingerprint })
        });
        const result = await response.json();
        if (result.success) {
            showDashboardNotification('success', result.message);
            setTimeout(() => location.reload(), 800);
        } else {
            showDashboardNotification('danger', result.error || 'Something went wrong while registering fingerprint');
        }
    } catch (e) {
        showDashboardNotification('danger', 'Network error. Please try again.');
    }
}

async function processFinanceAttendance() {
    const fakeFingerprint = getOrCreateFingerprint('mtcgs_finance_fingerprint', 'finance_fingerprint');
    try {
        const response = await fetch('/api/biometric/finance-attendance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ fingerprint_data: fakeFingerprint })
        });
        const result = await response.json();
        if (result.success) {
            showDashboardNotification('success', result.message);
            setTimeout(() => location.reload(), 800);
        } else {
            showDashboardNotification('danger', result.error || 'Please follow schedule');
        }
    } catch (e) {
        showDashboardNotification('danger', 'Network error. Please try again.');
    }
}

async function loadUnregisteredEmployees() {
    try {
        const response = await fetch('/api/biometric/unregistered-employees', {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        });
        const result = await response.json();
        if (result.success && result.data && result.data.length > 0) {
            let html = '<div class="list-group">';
            result.data.forEach(emp => {
                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">${emp.name}</div>
                            <small class="text-muted">${emp.employee_number || 'N/A'} - ${emp.position || 'Staff'}</small>
                        </div>
                        <button onclick="registerEmployeeFingerprint(${emp.id}, '${emp.name}')" class="btn btn-sm btn-primary">
                            <i class="fas fa-fingerprint"></i> Register
                        </button>
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('employeeBiometricList').innerHTML = html;
        } else {
            document.getElementById('employeeBiometricList').innerHTML = `
                <div class="alert alert-success text-center mb-0">
                    <i class="fas fa-check-circle"></i> All employees have registered fingerprints!
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('employeeBiometricList').innerHTML = `
            <div class="alert alert-danger text-center mb-0">
                <i class="fas fa-exclamation-triangle"></i> Failed to load employees. Please refresh the page.
            </div>
        `;
    }
}

async function registerEmployeeFingerprint(employeeId, employeeName) {
    const fakeFingerprint = btoa('employee_fingerprint_' + Date.now());
    try {
        const response = await fetch('/api/biometric/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ employee_id: employeeId, fingerprint_data: fakeFingerprint })
        });
        const result = await response.json();
        if (result.success) {
            showDashboardNotification('success', `Fingerprint registered for ${employeeName}`);
            loadUnregisteredEmployees();
        } else {
            showDashboardNotification('danger', result.error || 'Unable to register fingerprint');
        }
    } catch (e) {
        showDashboardNotification('danger', 'Network error. Please try again.');
    }
}

loadUnregisteredEmployees();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views/finance/dashboard.blade.php ENDPATH**/ ?>