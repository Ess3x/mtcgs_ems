<?php $__env->startSection('title', 'MTCGS Unified Demo - Lahat ng Features'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-0">
                        <i class="fas fa-play-circle text-primary"></i> MTCGS Unified Demo
                    </h1>
                    <p class="text-muted mt-2">Lahat ng features ng system sa iisang page - Interactive Demo</p>
                </div>
                <div>
                    <a href="<?php echo e(route('admin.employees')); ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="demoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="employee-tab" data-bs-toggle="tab" data-bs-target="#employee-demo" type="button" role="tab">
                                <i class="fas fa-user-plus"></i> Employee Creation
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="fingerprint-tab" data-bs-toggle="tab" data-bs-target="#fingerprint-demo" type="button" role="tab">
                                <i class="fas fa-fingerprint"></i> Fingerprint Registration
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance-demo" type="button" role="tab">
                                <i class="fas fa-clock"></i> Attendance Recording
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notification-tab" data-bs-toggle="tab" data-bs-target="#notification-demo" type="button" role="tab">
                                <i class="fas fa-bell"></i> Notifications
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dtr-tab" data-bs-toggle="tab" data-bs-target="#dtr-demo" type="button" role="tab">
                                <i class="fas fa-calendar-alt"></i> DTR View
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="demoTabsContent">
                        <!-- Employee Creation Demo -->
                        <div class="tab-pane fade show active" id="employee-demo" role="tabpanel">
                            <?php echo $__env->make('admin.demo.employee-creation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>

                        <!-- Fingerprint Registration Demo -->
                        <div class="tab-pane fade" id="fingerprint-demo" role="tabpanel">
                            <?php echo $__env->make('admin.demo.fingerprint-registration', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>

                        <!-- Attendance Recording Demo -->
                        <div class="tab-pane fade" id="attendance-demo" role="tabpanel">
                            <?php echo $__env->make('admin.demo.attendance-recording', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>

                        <!-- Notification Demo -->
                        <div class="tab-pane fade" id="notification-demo" role="tabpanel">
                            <?php echo $__env->make('admin.demo.notifications', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>

                        <!-- DTR Demo -->
                        <div class="tab-pane fade" id="dtr-demo" role="tabpanel">
                            <?php echo $__env->make('admin.demo.dtr-view', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Status Bar -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="demo-status-item" id="status-employee">
                                <i class="fas fa-user-plus fa-2x text-secondary"></i>
                                <p class="mt-2 mb-0 small">Employee Creation</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="demo-status-item" id="status-fingerprint">
                                <i class="fas fa-fingerprint fa-2x text-secondary"></i>
                                <p class="mt-2 mb-0 small">Fingerprint</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="demo-status-item" id="status-attendance">
                                <i class="fas fa-clock fa-2x text-secondary"></i>
                                <p class="mt-2 mb-0 small">Attendance</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="demo-status-item" id="status-notification">
                                <i class="fas fa-bell fa-2x text-secondary"></i>
                                <p class="mt-2 mb-0 small">Notifications</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="demo-status-item" id="status-dtr">
                                <i class="fas fa-calendar-alt fa-2x text-secondary"></i>
                                <p class="mt-2 mb-0 small">DTR View</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="demo-status-item" id="status-complete">
                                <i class="fas fa-check-circle fa-2x text-secondary"></i>
                                <p class="mt-2 mb-0 small">Complete</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.demo-status-item.completed i {
    color: #28a745 !important;
}
.demo-status-item.active i {
    color: #007bff !important;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
}

.nav-tabs .nav-link.active {
    border-bottom-color: #007bff;
    background-color: transparent;
}

.card-header-tabs {
    border-bottom: 1px solid #dee2e6;
}
</style>

<script>
// Demo Status Management
function updateDemoStatus(step, status) {
    const element = document.getElementById('status-' + step);
    element.classList.remove('completed', 'active');

    if (status === 'completed') {
        element.classList.add('completed');
    } else if (status === 'active') {
        element.classList.add('active');
    }
}

// Tab change handler
document.getElementById('demoTabs').addEventListener('shown.bs.tab', function (event) {
    const targetId = event.target.getAttribute('data-bs-target').substring(1);
    const step = targetId.replace('-demo', '');

    // Reset all statuses
    ['employee', 'fingerprint', 'attendance', 'notification', 'dtr'].forEach(s => {
        updateDemoStatus(s, 'pending');
    });

    // Set current as active
    updateDemoStatus(step, 'active');
});

// Initialize first tab as active
updateDemoStatus('employee', 'active');
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\unified-demo.blade.php ENDPATH**/ ?>