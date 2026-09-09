<?php $__env->startSection('title', 'Edit User'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit text-warning"></i> Edit <?php echo e(ucfirst($role)); ?>

                    </h4>
                </div>
                <div class="card-body">
                    <?php if($role === 'finance' && Auth::user()->admin_type === 'branch_admin'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Your changes will be sent to the System Administrator for approval before they take effect.
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo e(route('admin.user-update', ['role' => $role, 'id' => $userData->id])); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo e($userData->first_name); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo e($userData->last_name); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" value="<?php echo e($userData->position); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gmail / Login Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo e(old('email', $userData->user->email ?? '')); ?>" required>
                            <?php if($role === 'admin' && ($userData->user->admin_type ?? null) === 'branch_admin'): ?>
                                <small class="text-muted">Changing the Branch Head email will generate a new password and send the new login credentials to the new Gmail address.</small>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($role === 'employee'): ?>
                            <div class="mb-3">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-control">
                                    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($branch->id); ?>" <?php echo e($userData->branch_id == $branch->id ? 'selected' : ''); ?>>
                                            <?php echo e($branch->branch_name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Basic Salary</label>
                                <input type="number" step="0.01" name="basic_salary" class="form-control" value="<?php echo e($userData->basic_salary); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <?php if($role === 'finance'): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="New Hire" <?php echo e(old('status', $userData->status ?? 'New Hire') === 'New Hire' ? 'selected' : ''); ?>>New Hire</option>
                                        <option value="Regular" <?php echo e(old('status', $userData->status ?? '') === 'Regular' ? 'selected' : ''); ?>>Regular</option>
                                        <option value="1-2 Years in Service" <?php echo e(old('status', $userData->status ?? '') === '1-2 Years in Service' ? 'selected' : ''); ?>>1-2 Years in Service</option>
                                        <option value="3+ Years of Service" <?php echo e(old('status', $userData->status ?? '') === '3+ Years of Service' ? 'selected' : ''); ?>>3+ Years of Service</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date Hired</label>
                                    <input type="date" name="date_hired" class="form-control" value="<?php echo e(old('date_hired', $userData->date_hired ? \Carbon\Carbon::parse($userData->date_hired)->format('Y-m-d') : '')); ?>">
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" name="can_process_payroll" class="form-check-input" <?php echo e($userData->can_process_payroll ? 'checked' : ''); ?>>
                                <label class="form-check-label">Can Process Payroll</label>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($role === 'admin'): ?>
                            <div class="mb-3">
                                <label class="form-label">Admin Level</label>
                                <select name="admin_level" class="form-control">
                                    <option value="admin" <?php echo e($userData->admin_level == 'admin' ? 'selected' : ''); ?>>Admin</option>
                                    <option value="super_admin" <?php echo e($userData->admin_level == 'super_admin' ? 'selected' : ''); ?>>Super Admin</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($role === 'finance'): ?>
                            <hr>
                            <div class="card border-warning mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">
                                        <i class="fas fa-fingerprint"></i> Fingerprint Registration
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Important:</strong> Fingerprint registration is required for attendance tracking. Please register the finance officer's fingerprint now.
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-label">Fingerprint Status</div>
                                                <div class="d-flex align-items-center">
                                                    <span id="fingerprint-status" class="d-none"></span>
                                                    <button type="button" id="register-fingerprint-btn" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-fingerprint"></i> Register Fingerprint
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="fingerprint-data" class="form-label">Fingerprint Data</label>
                                                <textarea name="fingerprint_data" id="fingerprint-data" class="form-control" rows="3" readonly placeholder="Fingerprint data will appear here after scanning..."></textarea>
                                                <small class="text-muted">This field will be populated automatically after fingerprint scanning.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="fingerprint-scanner" class="d-none">
                                        <div class="border rounded p-3 bg-light">
                                            <h6>Fingerprint Scanner</h6>
                                            <div class="text-center mb-3">
                                                <div id="scanner-status" class="alert alert-info">
                                                    <i class="fas fa-info-circle"></i> Click "Start Scanning" to begin fingerprint registration
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <button type="button" id="start-scan-btn" class="btn btn-success me-2">
                                                    <i class="fas fa-play"></i> Start Scanning
                                                </button>
                                                <button type="button" id="stop-scan-btn" class="btn btn-danger" disabled>
                                                    <i class="fas fa-stop"></i> Stop Scanning
                                                </button>
                                            </div>
                                            <div class="mt-3">
                                                <div class="progress d-none" id="scan-progress">
                                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Active/Inactive Toggle -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Account Status</label>
                            <div class="form-check form-switch">
                                <input type="checkbox" name="is_active" class="form-check-input" id="activeSwitch" <?php echo e(($userData->user && $userData->user->is_active) ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="activeSwitch">
                                    <?php if($userData->user && $userData->user->is_active): ?>
                                        <span class="badge bg-success">Active - User can login</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive - User cannot login</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <small class="text-muted">Toggle to activate or deactivate this account</small>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo e(route('admin.user-management')); ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update badge when switch is toggled
    const activeSwitch = document.getElementById('activeSwitch');
    const statusLabel = document.querySelector('#activeSwitch + label');
    
    if (activeSwitch && statusLabel) {
        activeSwitch.addEventListener('change', function() {
            if (this.checked) {
                statusLabel.innerHTML = '<span class="badge bg-success">Active - User can login</span>';
            } else {
                statusLabel.innerHTML = '<span class="badge bg-danger">Inactive - User cannot login</span>';
            }
        });
    }

    <?php if($role === 'finance'): ?>
        let isScanning = false;
        let fingerprintCheckInterval = null;

        const registerFingerprintBtn = document.getElementById('register-fingerprint-btn');
        const fingerprintScanner = document.getElementById('fingerprint-scanner');
        const fingerprintData = document.getElementById('fingerprint-data');
        const fingerprintStatus = document.getElementById('fingerprint-status');
        const startScanBtn = document.getElementById('start-scan-btn');
        const stopScanBtn = document.getElementById('stop-scan-btn');
        const scannerStatus = document.getElementById('scanner-status');
        const scanProgress = document.getElementById('scan-progress');
        const progressBar = scanProgress ? scanProgress.querySelector('.progress-bar') : null;

        function launchEnrollmentApp() {
            fingerprintData.value = '';
            fingerprintStatus.classList.add('d-none');

            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            const employeeNumber = '<?php echo e($userData->employee_number ?? ''); ?>';
            const email = '<?php echo e($userData->user->email ?? ''); ?>';
            const role = 'finance';
            const position = document.querySelector('input[name="position"]').value.trim();
            const branchName = '<?php echo e($userData->branch->branch_name ?? ''); ?>';

            if (!firstName || !lastName) {
                alert('Please fill in First Name and Last Name before launching fingerprint enrollment.');
                return;
            }

            const uri = 'mtcgs-enroll:/register' +
                '?first_name=' + encodeURIComponent(firstName) +
                '&last_name=' + encodeURIComponent(lastName) +
                '&employee_number=' + encodeURIComponent(employeeNumber) +
                '&email=' + encodeURIComponent(email) +
                '&role=' + encodeURIComponent(role) +
                '&branch=' + encodeURIComponent(branchName) +
                '&position=' + encodeURIComponent(position);

            scannerStatus.className = 'alert alert-info';
            scannerStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Launching fingerprint enrollment app...';
            fingerprintScanner.classList.remove('d-none');
            startFingerprintPolling(employeeNumber);

            try {
                window.location.href = uri;
                fingerprintStatus.className = 'badge bg-warning';
                fingerprintStatus.innerHTML = '<i class="fas fa-external-link-alt"></i> App Launched';
                registerFingerprintBtn.disabled = false;
                registerFingerprintBtn.innerHTML = '<i class="fas fa-fingerprint"></i> Register Fingerprint';
                registerFingerprintBtn.classList.remove('btn-success');
                registerFingerprintBtn.classList.add('btn-outline-primary');
                scannerStatus.className = 'alert alert-info';
                scannerStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Waiting for fingerprint capture from desktop app...';
            } catch (error) {
                scannerStatus.className = 'alert alert-danger';
                scannerStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Could not launch the enrollment app. Please make sure the custom URI handler is installed.';
            }
        }

        function startFingerprintPolling(employeeNumber) {
            if (fingerprintCheckInterval) {
                clearInterval(fingerprintCheckInterval);
            }

            const employeeNumberForPolling = String(employeeNumber || '').trim();

            fingerprintCheckInterval = setInterval(async function() {
                try {
                    const url = '/api/fingerprint-temp?employee_number=' + encodeURIComponent(employeeNumberForPolling);
                    const response = await fetch(url);
                    if (!response.ok) {
                        return;
                    }

                    const result = await response.json();
                    if (result.success && result.data && result.data.fingerprint_data) {
                        const incomingData = result.data.fingerprint_data;
                        const incomingEmpNumber = String(result.data.employee_number || '').trim();

                        if (!incomingData || incomingData.trim().length < 25) {
                            return;
                        }

                        fingerprintStatus.classList.remove('d-none');

                        if (incomingEmpNumber.toLowerCase() === employeeNumber.toLowerCase()) {
                            clearInterval(fingerprintCheckInterval);
                            fingerprintCheckInterval = null;
                            fingerprintData.value = incomingData;

                            fingerprintStatus.className = 'badge bg-success me-2';
                            fingerprintStatus.innerHTML = '<i class="fas fa-check-circle"></i> Registered ✓';
                            registerFingerprintBtn.disabled = false;
                            registerFingerprintBtn.innerHTML = '<i class="fas fa-redo"></i> Register Again';
                            registerFingerprintBtn.classList.remove('btn-success');
                            registerFingerprintBtn.classList.add('btn-outline-warning');
                            scannerStatus.className = 'alert alert-success';
                            scannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> ✓ Fingerprint matched! Click "Register Again" to rescan if needed.';
                        } else {
                            fingerprintStatus.className = 'badge bg-warning me-2';
                            fingerprintStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Employee ID Mismatch';
                            scannerStatus.className = 'alert alert-warning';
                            scannerStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Fingerprint is for ' + incomingEmpNumber + '</strong> but this employee is <strong>' + employeeNumberForPolling + '</strong>.';
                            registerFingerprintBtn.disabled = false;
                            registerFingerprintBtn.innerHTML = '<i class="fas fa-fingerprint"></i> Scan Again';
                        }
                    }
                } catch (error) {
                    console.log('Polling for fingerprint data...', error);
                }
            }, 180);
        }

        function resumeFingerprintPolling() {
            if (!fingerprintData.value.trim() && !fingerprintCheckInterval && !document.hidden) {
                startFingerprintPolling(String('<?php echo e($userData->employee_number ?? ''); ?>').trim());
            }
        }

        registerFingerprintBtn.addEventListener('click', function() {
            launchEnrollmentApp();
        });

        startScanBtn.addEventListener('click', function() {
            launchEnrollmentApp();
        });

        stopScanBtn.addEventListener('click', function() {
            if (fingerprintCheckInterval) {
                clearInterval(fingerprintCheckInterval);
                fingerprintCheckInterval = null;
            }
            scannerStatus.className = 'alert alert-secondary';
            scannerStatus.innerHTML = '<i class="fas fa-stop"></i> Enrollment app launch cancelled.';
        });

        window.addEventListener('focus', resumeFingerprintPolling);
        document.addEventListener('visibilitychange', resumeFingerprintPolling);

        window.addEventListener('beforeunload', function() {
            if (fingerprintCheckInterval) {
                clearInterval(fingerprintCheckInterval);
                fingerprintCheckInterval = null;
            }
        });

        document.querySelector('form').addEventListener('submit', function() {
            if (fingerprintCheckInterval) {
                clearInterval(fingerprintCheckInterval);
                fingerprintCheckInterval = null;
            }
        });
    <?php endif; ?>
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\user-edit.blade.php ENDPATH**/ ?>