<?php $__env->startSection('title', 'Manage Employee Creation Authority'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-2"><i class="fas fa-shield-alt me-2"></i>Employee Creation Authority Management</h2>
            <p class="text-muted mb-0">Grant or revoke authority for Branch Admins and Finance Officers to create employee accounts</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 small">Branch Admins with Authority</p>
                            <h3 class="mb-0"><?php echo e($branchAdmins->filter(fn($a) => $a['can_create_employees'])->count()); ?></h3>
                            <small>out of <?php echo e($branchAdmins->count()); ?></small>
                        </div>
                        <i class="fas fa-user-tie fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 small">Finance Officers with Authority</p>
                            <h3 class="mb-0"><?php echo e($financeOfficers->filter(fn($f) => $f['can_create_employees'])->count()); ?></h3>
                            <small>out of <?php echo e($financeOfficers->count()); ?></small>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 small">Total Authorized Users</p>
                            <h3 class="mb-0"><?php echo e($branchAdmins->filter(fn($a) => $a['can_create_employees'])->count() + $financeOfficers->filter(fn($f) => $f['can_create_employees'])->count()); ?></h3>
                            <small>can create employees</small>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Admins Authority -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Branch Admins</h5>
        </div>
        <div class="card-body">
            <?php if($branchAdmins->isEmpty()): ?>
                <div class="alert alert-info mb-0">No branch admins found</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Branch</th>
                                <th>Can Create Employees</th>
                                <th>Can Manage Accounts</th>
                                <th>Authority Granted</th>
                                <th style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $branchAdmins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($admin['name']); ?></strong>
                                    </td>
                                    <td><?php echo e($admin['branch']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo e($admin['can_create_employees'] ? 'success' : 'secondary'); ?>">
                                            <?php echo e($admin['can_create_employees'] ? '✓ Yes' : '✗ No'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo e($admin['can_manage_accounts'] ? 'success' : 'secondary'); ?>">
                                            <?php echo e($admin['can_manage_accounts'] ? '✓ Yes' : '✗ No'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <?php if($admin['authority_granted_at']): ?>
                                            <small class="text-muted"><?php echo e(\Carbon\Carbon::parse($admin['authority_granted_at'])->format('M d, Y')); ?></small>
                                            <?php if($admin['granted_by']): ?>
                                                <br><small class="text-muted">by <?php echo e($admin['granted_by']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($admin['can_create_employees']): ?>
                                            <button class="btn btn-sm btn-danger revoke-admin-btn" data-admin-id="<?php echo e($admin['id']); ?>" title="Revoke Authority">
                                                <i class="fas fa-times"></i> Revoke
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success grant-admin-btn" data-admin-id="<?php echo e($admin['id']); ?>" title="Grant Authority">
                                                <i class="fas fa-check"></i> Grant
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Finance Officers Authority -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Finance Officers</h5>
        </div>
        <div class="card-body">
            <?php if($financeOfficers->isEmpty()): ?>
                <div class="alert alert-info mb-0">No finance officers found</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Branch</th>
                                <th>Can Create Employees</th>
                                <th>Can Manage Accounts</th>
                                <th>Authority Granted</th>
                                <th style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $financeOfficers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $finance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($finance['name']); ?></strong>
                                    </td>
                                    <td><?php echo e($finance['branch']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo e($finance['can_create_employees'] ? 'success' : 'secondary'); ?>">
                                            <?php echo e($finance['can_create_employees'] ? '✓ Yes' : '✗ No'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo e($finance['can_manage_accounts'] ? 'success' : 'secondary'); ?>">
                                            <?php echo e($finance['can_manage_accounts'] ? '✓ Yes' : '✗ No'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <?php if($finance['authority_granted_at']): ?>
                                            <small class="text-muted"><?php echo e(\Carbon\Carbon::parse($finance['authority_granted_at'])->format('M d, Y')); ?></small>
                                            <?php if($finance['granted_by']): ?>
                                                <br><small class="text-muted">by <?php echo e($finance['granted_by']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($finance['can_create_employees']): ?>
                                            <button class="btn btn-sm btn-danger revoke-finance-btn" data-finance-id="<?php echo e($finance['id']); ?>" title="Revoke Authority">
                                                <i class="fas fa-times"></i> Revoke
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success grant-finance-btn" data-finance-id="<?php echo e($finance['id']); ?>" title="Grant Authority">
                                                <i class="fas fa-check"></i> Grant
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Grant Authority Modal (Branch Admin) -->
<div class="modal fade" id="grantAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Grant Employee Creation Authority</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="adminGrantMsg" class="mb-3"></p>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="adminCanCreateEmployees" checked>
                    <label class="form-check-label" for="adminCanCreateEmployees">
                        <strong>Can Create Employees</strong>
                        <div class="small text-muted">Allow to create new employee accounts</div>
                    </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="adminCanManageAccounts">
                    <label class="form-check-label" for="adminCanManageAccounts">
                        <strong>Can Manage Accounts</strong>
                        <div class="small text-muted">Allow to edit and manage employee accounts</div>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmGrantAdminBtn">Grant Authority</button>
            </div>
        </div>
    </div>
</div>

<!-- Grant Authority Modal (Finance Officer) -->
<div class="modal fade" id="grantFinanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Grant Employee Creation Authority</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="financeGrantMsg" class="mb-3"></p>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="financeCanCreateEmployees" checked>
                    <label class="form-check-label" for="financeCanCreateEmployees">
                        <strong>Can Create Employees</strong>
                        <div class="small text-muted">Allow to create new employee accounts</div>
                    </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="financeCanManageAccounts">
                    <label class="form-check-label" for="financeCanManageAccounts">
                        <strong>Can Manage Accounts</strong>
                        <div class="small text-muted">Allow to edit and manage employee accounts</div>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmGrantFinanceBtn">Grant Authority</button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let selectedAdminId = null;
let selectedFinanceId = null;

// Grant Admin Authority
document.querySelectorAll('.grant-admin-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        selectedAdminId = this.dataset.adminId;
        document.getElementById('adminGrantMsg').textContent = 'Configure permissions for this admin:';
        const modal = new bootstrap.Modal(document.getElementById('grantAdminModal'));
        modal.show();
    });
});

// Grant Finance Authority
document.querySelectorAll('.grant-finance-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        selectedFinanceId = this.dataset.financeId;
        document.getElementById('financeGrantMsg').textContent = 'Configure permissions for this finance officer:';
        const modal = new bootstrap.Modal(document.getElementById('grantFinanceModal'));
        modal.show();
    });
});

// Confirm Grant Admin
document.getElementById('confirmGrantAdminBtn').addEventListener('click', function() {
    if (!selectedAdminId) return;
    
    const data = {
        can_create_employees: document.getElementById('adminCanCreateEmployees').checked,
        can_manage_accounts: document.getElementById('adminCanManageAccounts').checked,
    };

    fetch(`/admin/authority/admin/${selectedAdminId}/grant`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(data),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.error || 'Failed to grant authority', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error: ' + error.message, 'danger');
    });

    bootstrap.Modal.getInstance(document.getElementById('grantAdminModal')).hide();
});

// Confirm Grant Finance
document.getElementById('confirmGrantFinanceBtn').addEventListener('click', function() {
    if (!selectedFinanceId) return;
    
    const data = {
        can_create_employees: document.getElementById('financeCanCreateEmployees').checked,
        can_manage_accounts: document.getElementById('financeCanManageAccounts').checked,
    };

    fetch(`/admin/authority/finance/${selectedFinanceId}/grant`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(data),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.error || 'Failed to grant authority', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error: ' + error.message, 'danger');
    });

    bootstrap.Modal.getInstance(document.getElementById('grantFinanceModal')).hide();
});

// Revoke Admin Authority
document.querySelectorAll('.revoke-admin-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('Are you sure you want to revoke authority from this admin?')) return;
        
        const adminId = this.dataset.adminId;
        
        fetch(`/admin/authority/admin/${adminId}/revoke`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.error || 'Failed to revoke authority', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error: ' + error.message, 'danger');
        });
    });
});

// Revoke Finance Authority
document.querySelectorAll('.revoke-finance-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('Are you sure you want to revoke authority from this finance officer?')) return;
        
        const financeId = this.dataset.financeId;
        
        fetch(`/admin/authority/finance/${financeId}/revoke`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.error || 'Failed to revoke authority', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error: ' + error.message, 'danger');
        });
    });
});

function showNotification(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\authority\index.blade.php ENDPATH**/ ?>