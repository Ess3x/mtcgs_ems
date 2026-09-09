<?php $__env->startSection('title', 'Branch Employees'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h2><i class="fas fa-users me-2"></i> Branch Employees</h2>
                    <p class="mb-0">Managing employees for <strong><?php echo e($branchName); ?></strong> branch</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Employee List</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Employee #</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Fingerprint Status</th>
                        <?php if(auth()->user()->role !== 'finance_officer'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($emp->employee_number); ?></td>
                        <td><?php echo e($emp->first_name); ?> <?php echo e($emp->last_name); ?></td>
                        <td><?php echo e($emp->position ?? 'N/A'); ?></td>
                        <td>
                            <?php if($emp->is_fingerprint_registered): ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Registered</span>
                            <?php else: ?>
                                <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Not Registered</span>
                            <?php endif; ?>
                        </td>
                        <?php if(auth()->user()->role !== 'finance_officer'): ?>
                            <td>
                                <a href="<?php echo e(route('finance.employee.attendance', $emp->id)); ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-calendar-alt"></i> Attendance
                                </a>
                                <?php if(!$emp->is_fingerprint_registered): ?>
                                    <button onclick="registerEmployeeFingerprint(<?php echo e($emp->id); ?>, '<?php echo e($emp->first_name); ?> <?php echo e($emp->last_name); ?>')" class="btn btn-sm btn-primary">
                                        <i class="fas fa-fingerprint"></i> Register
                                    </button>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="text-center">No employees found in this branch</small></td>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function registerEmployeeFingerprint(employeeId, employeeName) {
    const fakeFingerprint = btoa('employee_fingerprint_' + Date.now());
    
    const response = await fetch('/api/biometric/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ employee_id: employeeId, fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(`✅ Fingerprint registered for ${employeeName}`);
        location.reload();
    } else {
        alert('Error: ' + result.error);
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\finance\employees.blade.php ENDPATH**/ ?>