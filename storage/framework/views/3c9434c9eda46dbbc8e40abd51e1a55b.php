<?php $__env->startSection('title', 'Branch Employees'); ?>

<?php $__env->startSection('content'); ?>
<style>
    body.dark-mode .finance-employees-page .card-header.bg-white,
    body.dark-mode .finance-employees-page .table thead.table-light th,
    body.dark-mode .finance-employees-page .table tbody tr.table-light > th {
        background-color: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #334155 !important;
    }

    body.dark-mode .finance-employees-page .table tbody tr.table-secondary > th {
        background-color: #334155 !important;
        color: #e2e8f0 !important;
        border-color: #475569 !important;
    }

    body.dark-mode .finance-employees-page a,
    body.dark-mode .finance-employees-page .text-primary {
        color: #FFD166 !important;
    }
</style>

<div class="container-fluid finance-employees-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h2><i class="fas fa-users me-2"></i> Branch Employees</h2>
                    <p class="mb-0">
                        Managing employees for <strong><?php echo e($branchName); ?></strong> branch
                        <?php if($buhiBranchId && $buhiBranchId != $branchId): ?>
                            and <strong><?php echo e($buhiBranchName); ?></strong> branch
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
                <h5 class="mb-0">All Users</h5>
        </div>
        <?php
            $isNonBranchAdmin = fn ($user) => $user->role === 'admin' && (in_array($user->admin_type, ['super_admin', 'hr'], true) || empty($user->admin_type));
            $employeeGroups = [];
            $nonBranchAdmins = $users->filter($isNonBranchAdmin)->values();
            if ($nonBranchAdmins->isNotEmpty()) {
                $employeeGroups[] = [
                    'name' => 'Super Admin / HR (No Branch)',
                    'users' => $nonBranchAdmins,
                ];
            }
            $employeeGroups[] = [
                'name' => $branchName,
                'users' => $users->filter(fn ($user) => !$isNonBranchAdmin($user) && ($user->profile?->branch_id ?? $user->branch_id) == $branchId)->values(),
            ];
            if ($buhiBranchId && $buhiBranchId != $branchId) {
                $employeeGroups[] = [
                    'name' => $buhiBranchName,
                    'users' => $users->filter(fn ($user) => !$isNonBranchAdmin($user) && ($user->profile?->branch_id ?? $user->branch_id) == $buhiBranchId)->values(),
                ];
            }
        ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Employee #</th>
                        <th>Profile</th>
                        <th>Role</th>
                        <th>Position</th>
                        <th>Fingerprint Status</th>
                        <?php if(auth()->user()->role === 'finance_officer'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $employeeGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($group['users']->isNotEmpty()): ?>
                            <tr class="table-light">
                                <th colspan="6" class="text-primary">
                                    <i class="fas fa-building me-2"></i><?php echo e($group['name']); ?>

                                    <span class="badge bg-primary ms-2"><?php echo e($group['users']->count()); ?></span>
                                </th>
                            </tr>
                            <?php
                                $roleGroups = $group['name'] === 'Super Admin / HR (No Branch)'
                                    ? collect(['Super Admin / HR' => $group['users']])
                                    : collect([
                                        'Branch Admin' => $group['users']->filter(fn ($user) => $user->role === 'admin' && $user->admin_type === 'branch_admin'),
                                        'Finance Officer' => $group['users']->filter(fn ($user) => in_array($user->role, ['finance_officer', 'finance_head'], true)),
                                        'Employees' => $group['users']->filter(fn ($user) => !in_array($user->role, ['admin', 'finance_officer', 'finance_head'], true)),
                                    ])->filter(fn ($roleUsers) => $roleUsers->isNotEmpty());
                            ?>
                            <?php $__currentLoopData = $roleGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roleName => $roleUsers): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="table-secondary">
                                    <th colspan="6" class="text-dark ps-4">
                                        <i class="fas fa-users me-2"></i><?php echo e($roleName); ?>

                                        <span class="badge bg-secondary ms-2"><?php echo e($roleUsers->count()); ?></span>
                                    </th>
                                </tr>
                                <?php $__currentLoopData = $roleUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $profile = $user->profile;
                                        $profileType = $profile ? strtolower(class_basename($profile)) : null;
                                        $profileName = $profile ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) : $user->name;
                                        $profileInitials = collect(explode(' ', $profileName))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                                        $profilePhoto = $profile?->profile_photo;
                                        $hasProfilePhoto = $profilePhoto && \Illuminate\Support\Facades\Storage::disk('public')->exists($profilePhoto);
                                        $profilePhotoUrl = $hasProfilePhoto ? asset('storage/' . ltrim($profilePhoto, '/')) : null;
                                        $canOpenProfile = (bool) $profile;
                                    ?>
                                    <tr>
                                        <td><?php echo e($profile?->employee_number ?? 'USER-' . $user->id); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if($profilePhotoUrl): ?>
                                                    <img src="<?php echo e($profilePhotoUrl . '?v=' . $profile->updated_at?->timestamp); ?>" alt="<?php echo e($profileName); ?>" class="rounded-circle border" style="width: 38px; height: 38px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: .85rem;"><?php echo e($profileInitials ?: '?'); ?></div>
                                                <?php endif; ?>
                                                <?php if($canOpenProfile): ?>
                                                    <a href="<?php echo e(route('finance.employee.profile', [strtolower(class_basename($profile)), $profile->id])); ?>" class="fw-semibold text-decoration-none"><?php echo e($profileName); ?></a>
                                                <?php else: ?>
                                                    <span class="fw-semibold"><?php echo e($profileName); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo e(str_replace('_', ' ', ucfirst($user->role ?? 'user'))); ?></td>
                                        <td><?php echo e($profile?->position ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if($profile?->is_fingerprint_registered): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Registered</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Not Registered</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if(auth()->user()->role === 'finance_officer'): ?>
                                            <td>
                                                <?php if($profile instanceof \App\Models\EmployeeProfile): ?>
                                                    <a href="<?php echo e(route('finance.employee.attendance', $profile->id)); ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-calendar-alt"></i> Attendance
                                                    </a>
                                                <?php endif; ?>
                                                <?php if($profile?->is_fingerprint_registered): ?>
                                                    <button type="button" class="btn btn-sm btn-success" disabled>
                                                        <i class="fas fa-check-circle"></i> Registered
                                                    </button>
                                                <?php elseif($profile): ?>
                                                    <button onclick="registerEmployeeFingerprint(<?php echo e($profile->id); ?>, '<?php echo e(addslashes($profileName)); ?>')" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-fingerprint"></i> Unregistered
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if($users->isEmpty()): ?>
                        <tr><td colspan="6" class="text-center">No active users found in the selected branches</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function registerEmployeeFingerprint(employeeId, employeeName) {
    const confirmed = window.confirm(`Are you sure you want to register the fingerprint for ${employeeName}?`);
    if (!confirmed) {
        return;
    }

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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\finance\employees.blade.php ENDPATH**/ ?>