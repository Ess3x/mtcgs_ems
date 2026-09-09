<?php $__env->startSection('title', 'DTR Summary'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="<?php echo e(route('employee.dtr.index')); ?>" class="btn btn-outline-secondary btn-sm mb-3">
                        <i class="fas fa-arrow-left"></i> Back to DTR
                    </a>
                    <h2 class="mb-2">DTR Summary</h2>
                    <p class="text-muted"><?php echo e($employeeProfile->first_name); ?> <?php echo e($employeeProfile->last_name); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Period -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Current Period</h5>
        </div>
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-primary mb-3"><?php echo e($currentDTR->period_start->format('M d')); ?> - <?php echo e($currentDTR->period_end->format('M d, Y')); ?></h6>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <p class="text-muted mb-1 small">Total Hours</p>
                            <h5 class="mb-0"><?php echo e(number_format($currentStats['total_hours'], 1)); ?><small class="text-muted"> hrs</small></h5>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1 small">Days Present</p>
                            <h5 class="mb-0"><?php echo e($currentStats['days_present']); ?><small class="text-muted">/<?php echo e($currentStats['working_days']); ?></small></h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <p class="text-muted mb-1 small">Overtime</p>
                            <h5 class="mb-0"><?php echo e(number_format($currentStats['total_overtime'], 1)); ?><small class="text-muted"> hrs</small></h5>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1 small">Status</p>
                            <span class="badge bg-<?php echo e($currentDTR->status === 'draft' ? 'warning' : ($currentDTR->status === 'submitted' ? 'info' : ($currentDTR->status === 'approved' ? 'success' : 'danger'))); ?> p-2">
                                <?php echo e(ucfirst($currentDTR->status)); ?>

                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="<?php echo e(route('employee.dtr.show', $currentDTR->id)); ?>" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-eye"></i> View Detailed DTR
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Previous Period -->
        <?php if($previousDTR): ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3"><?php echo e($previousDTR->period_start->format('M d')); ?> - <?php echo e($previousDTR->period_end->format('M d, Y')); ?></h6>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <p class="text-muted mb-1 small">Total Hours</p>
                                <h5 class="mb-0"><?php echo e(number_format($previousStats['total_hours'], 1)); ?><small class="text-muted"> hrs</small></h5>
                            </div>
                            <div class="col-6">
                                <p class="text-muted mb-1 small">Days Present</p>
                                <h5 class="mb-0"><?php echo e($previousStats['days_present']); ?><small class="text-muted">/<?php echo e($previousStats['working_days']); ?></small></h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-1 small">Overtime</p>
                                <h5 class="mb-0"><?php echo e(number_format($previousStats['total_overtime'], 1)); ?><small class="text-muted"> hrs</small></h5>
                            </div>
                            <div class="col-6">
                                <p class="text-muted mb-1 small">Status</p>
                                <span class="badge bg-<?php echo e($previousDTR->status === 'draft' ? 'warning' : ($previousDTR->status === 'submitted' ? 'info' : ($previousDTR->status === 'approved' ? 'success' : 'danger'))); ?> p-2">
                                    <?php echo e(ucfirst($previousDTR->status)); ?>

                                </span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="<?php echo e(route('employee.dtr.show', $previousDTR->id)); ?>" class="btn btn-sm btn-outline-primary w-100">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Comparison Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Performance Comparison</h5>
                </div>
                <div class="card-body">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('comparisonChart').getContext('2d');
    
    <?php
        $currentLabel = $currentDTR->period_start->format('M d');
        $previousLabel = $previousDTR ? $previousDTR->period_start->format('M d') : 'N/A';
        $currentHours = $currentStats['total_hours'];
        $previousHours = $previousStats ? $previousStats['total_hours'] : 0;
        $currentOT = $currentStats['total_overtime'];
        $previousOT = $previousStats ? $previousStats['total_overtime'] : 0;
    ?>

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['<?php echo e($currentLabel); ?>', '<?php echo e($previousLabel); ?>'],
            datasets: [
                {
                    label: 'Total Hours',
                    data: [<?php echo e($currentHours); ?>, <?php echo e($previousHours); ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Overtime Hours',
                    data: [<?php echo e($currentOT); ?>, <?php echo e($previousOT); ?>],
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Hours'
                    }
                }
            }
        }
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\employee\dtr\summary.blade.php ENDPATH**/ ?>