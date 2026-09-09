<?php $__env->startSection('title', 'Biometric Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h2><i class="fas fa-fingerprint me-2"></i> Biometric Management</h2>
                    <p class="mb-0">Manage fingerprint registrations for users in your branch and handle admin biometric setup.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin / Branch Head's Own DTR Card -->
    <?php
        $user = Auth::user();
        $adminProfile = $user ? $user->getAdminProfile() : null;
        $branchHeadProfile = $user ? $user->getBranchHeadProfile() : null;
        $employeeProfile = $adminProfile ? App\Models\EmployeeProfile::find($adminProfile->employee_profile_id) : ($branchHeadProfile ? App\Models\EmployeeProfile::find($branchHeadProfile->employee_profile_id) : null);
        $todayAttendance = $employeeProfile ? App\Models\AttendanceLog::where('employee_profile_id', $employeeProfile->id)->whereDate('attendance_date', today())->first() : null;
        $canSelfRegisterAdminFingerprint = $user && ($user->isSuperAdmin() || $user->role === 'branch_head' || $user->role === 'admin');
        $isBranchHeadSelfAttendance = $user && $user->role === 'branch_head';
    ?>
    
    <?php if($employeeProfile && $canSelfRegisterAdminFingerprint): ?>
    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-shield text-danger me-2"></i> <?php echo e($isBranchHeadSelfAttendance ? 'My Attendance (Branch Head)' : 'My Attendance (Admin)'); ?></h5>
                </div>
                <div class="card-body text-center">
                    <div id="adminAttendanceStatus">
                        <?php if(!$adminProfile->is_fingerprint_registered): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Please register your fingerprint first.
                            </div>
                            <button onclick="registerAdminFingerprint()" class="btn btn-primary">
                                <i class="fas fa-fingerprint"></i> Register My Fingerprint
                            </button>
                        <?php elseif(!$todayAttendance || !$todayAttendance->am_in || !$todayAttendance->am_out || !$todayAttendance->pm_in || !$todayAttendance->pm_out): ?>
                            <?php
                                $adminAttendanceButtonClass = 'success';
                                if ($todayAttendance && $todayAttendance->am_in && !$todayAttendance->am_out) {
                                    $adminAttendanceButtonClass = 'warning';
                                } elseif ($todayAttendance && $todayAttendance->am_out && !$todayAttendance->pm_in) {
                                    $adminAttendanceButtonClass = 'info';
                                } elseif ($todayAttendance && $todayAttendance->pm_in && !$todayAttendance->pm_out) {
                                    $adminAttendanceButtonClass = 'danger';
                                }
                            ?>
                            <button onclick="processAdminAttendance()" class="btn btn-<?php echo e($adminAttendanceButtonClass); ?> btn-lg px-5 py-3">
                                <i class="fas fa-fingerprint fa-2x d-block mb-2"></i>
                                FINGERPRINT ATTENDANCE
                            </button>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Completed for Today
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Register Employees Section -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-users text-primary me-2"></i> Employee Fingerprint Registration</h5>
        </div>
        <div class="card-body">
            <div id="unregisteredEmployeesList">
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm"></div> Loading employees...
                </div>
            </div>
        </div>
    </div>

    <?php if(Auth::user()->isSuperAdmin()): ?>
        <!-- Register Finance Officers Section (Super Admin only) -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-line text-info me-2"></i> Finance Officer Fingerprint Registration</h5>
            </div>
            <div class="card-body">
                <div id="unregisteredFinanceList">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm"></div> Loading finance officers...
                    </div>
                </div>
            </div>
        </div>

        <!-- Register Admins Section (Super Admin only) -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-user-shield text-danger me-2"></i> Admin Fingerprint Registration</h5>
            </div>
            <div class="card-body">
                <div id="unregisteredAdminsList">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm"></div> Loading admins...
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const jsonHeaders = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': csrfToken
};

function getOrCreateFingerprint(key, seed) {
    const stored = localStorage.getItem(key);
    if (stored) {
        return stored;
    }

    const generated = btoa(`${seed}_${Date.now()}`);
    localStorage.setItem(key, generated);
    return generated;
}

// Admin's own fingerprint registration
async function registerAdminFingerprint() {
    const user = {
        name: '<?php echo e(Auth::user()->name); ?>',
        email: '<?php echo e(Auth::user()->email); ?>',
        role: 'admin'
    };
    
    // Launch C# Fingerprint Enrollment App
    const uri = 'mtcgs-enroll:/register' +
        '?name=' + encodeURIComponent(user.name) +
        '&email=' + encodeURIComponent(user.email) +
        '&role=' + encodeURIComponent(user.role) +
        '&type=admin';
    
    try {
        window.location.href = uri;
        alert('Launching Fingerprint Enrollment App...\nPlease scan your fingerprint when the application opens.');
        
        // Poll for fingerprint data from the C# app
        let attempts = 0;
        const pollInterval = setInterval(async () => {
            attempts++;
            
            // Check if fingerprint was registered (max 2 minutes)
            if (attempts > 120) {
                clearInterval(pollInterval);
                return;
            }
            
            const response = await fetch('/api/biometric/admin-status', {
                headers: jsonHeaders
            });
            
            const result = await response.json();
            
            if (result.data && result.data.is_fingerprint_registered) {
                clearInterval(pollInterval);
                alert('✓ Fingerprint registered successfully!');
                location.reload();
            }
        }, 1000);
        
    } catch (error) {
        alert('Could not launch C# app. Please ensure the mtcgs-enroll URI handler is registered.\n\nRun this in PowerShell as Administrator:\nreg import "c:\\xampp\\htdocs\\mtcgs-main_08-13-26\\mtcgs-ems\\biometric\\mtcgs-enroll.reg"');
    }
}

async function processAdminAttendance() {
    const fakeFingerprint = getOrCreateFingerprint('mtcgs_admin_fingerprint', 'admin_fingerprint');
    
    const response = await fetch('/api/biometric/admin-attendance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...jsonHeaders
        },
        body: JSON.stringify({ fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(result.message);
        location.reload();
    } else {
        alert('Error: ' + (result.error || 'Please follow schedule'));
    }
}

// Load unregistered employees
async function loadUnregisteredEmployees() {
    const response = await fetch('/api/biometric/unregistered-employees', {
        headers: jsonHeaders
    });
    
    const result = await response.json();
    
    if (result.success && result.data.length > 0) {
        let html = '<div class="list-group">';
        result.data.forEach(emp => {
            html += `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${emp.name}</strong><br>
                            <small class="text-muted">${emp.employee_number} - ${emp.position || 'Staff'}</small>
                        </div>
                        <button onclick="registerEmployeeFingerprint(${emp.id}, '${emp.name}')" class="btn btn-sm btn-primary">
                            <i class="fas fa-fingerprint"></i> Register
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        document.getElementById('unregisteredEmployeesList').innerHTML = html;
    } else {
        document.getElementById('unregisteredEmployeesList').innerHTML = '<div class="alert alert-success">All employees have registered fingerprints!</div>';
    }
}

// Load unregistered finance officers
async function loadUnregisteredFinanceOfficers() {
    const financeList = document.getElementById('unregisteredFinanceList');
    if (!financeList) {
        return;
    }

    const response = await fetch('/api/biometric/unregistered-finance-officers', {
        headers: jsonHeaders
    });
    
    const result = await response.json();
    
    if (result.success && result.data.length > 0) {
        let html = '<div class="list-group">';
        result.data.forEach(fin => {
            html += `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${fin.name}</strong><br>
                            <small class="text-muted">${fin.email} - ${fin.employee_number}</small>
                        </div>
                        <button onclick="registerFinanceOfficerFingerprint(${fin.id}, '${fin.name}')" class="btn btn-sm btn-info">
                            <i class="fas fa-fingerprint"></i> Register
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        financeList.innerHTML = html;
    } else {
        financeList.innerHTML = '<div class="alert alert-success">All finance officers have registered fingerprints!</div>';
    }
}

// Load unregistered admins
async function loadUnregisteredAdmins() {
    const adminList = document.getElementById('unregisteredAdminsList');
    if (!adminList) {
        return;
    }

    const response = await fetch('/api/biometric/unregistered-admins', {
        headers: jsonHeaders
    });
    
    const result = await response.json();
    
    if (result.success && result.data.length > 0) {
        let html = '<div class="list-group">';
        result.data.forEach(admin => {
            html += `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${admin.name}</strong><br>
                            <small class="text-muted">${admin.email} - ${admin.admin_level}</small>
                        </div>
                        <button onclick="registerAdminOfficerFingerprint(${admin.id}, '${admin.name}')" class="btn btn-sm btn-danger">
                            <i class="fas fa-fingerprint"></i> Register
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        adminList.innerHTML = html;
    } else {
        adminList.innerHTML = '<div class="alert alert-success">All admins have registered fingerprints!</div>';
    }
}

// Register employee fingerprint
async function registerEmployeeFingerprint(employeeId, employeeName) {
    const fakeFingerprint = btoa('employee_fingerprint_' + Date.now());
    
    const response = await fetch('/api/biometric/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...jsonHeaders
        },
        body: JSON.stringify({ employee_id: employeeId, fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(`✅ Fingerprint registered for ${employeeName}`);
        await loadUnregisteredEmployees();
    } else {
        alert('Error: ' + result.error);
    }
}

// Register finance officer fingerprint (Super Admin)
async function registerFinanceOfficerFingerprint(financeId, financeName) {
    const fakeFingerprint = btoa('finance_officer_fingerprint_' + Date.now());
    
    const response = await fetch('/api/biometric/register-finance-officer', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...jsonHeaders
        },
        body: JSON.stringify({ finance_id: financeId, fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(`✅ Fingerprint registered for finance officer: ${financeName}`);
        await loadUnregisteredFinanceOfficers();
    } else {
        alert('Error: ' + result.error);
    }
}

// Register admin fingerprint (Super Admin)
async function registerAdminOfficerFingerprint(adminId, adminName) {
    const fakeFingerprint = btoa('admin_officer_fingerprint_' + Date.now());
    
    const response = await fetch('/api/biometric/register-admin-officer', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...jsonHeaders
        },
        body: JSON.stringify({ admin_id: adminId, fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(`✅ Fingerprint registered for admin: ${adminName}`);
        await loadUnregisteredAdmins();
    } else {
        alert('Error: ' + result.error);
    }
}

// Load all sections
loadUnregisteredEmployees();
if (document.getElementById('unregisteredFinanceList')) {
    loadUnregisteredFinanceOfficers();
}
if (document.getElementById('unregisteredAdminsList')) {
    loadUnregisteredAdmins();
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\biometric.blade.php ENDPATH**/ ?>